import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/vendor_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../widgets/terms_modal.dart';
import '../../services/api_service.dart';
import 'package:go_router/go_router.dart';

class VendorApplyScreen extends StatefulWidget {
  const VendorApplyScreen({super.key});

  @override
  State<VendorApplyScreen> createState() => _VendorApplyScreenState();
}

class _VendorApplyScreenState extends State<VendorApplyScreen> {
  // Terms Modal
  bool _showTermsModal = true;
  bool _acceptedTerms = false;

  // User fields (for non-logged in users)
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _passwordConfirmationController = TextEditingController();

  // Vendor fields
  final TextEditingController _storeNameController = TextEditingController();
  final TextEditingController _descriptionController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _cityController = TextEditingController();
  final TextEditingController _stateController = TextEditingController();
  final TextEditingController _countryController = TextEditingController(text: 'Guinée');
  final TextEditingController _zipCodeController = TextEditingController();

  int? _selectedCurrencyId;
  File? _logoFile;
  String? _logoPath;

  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  List<Map<String, dynamic>> _currencies = [];

  @override
  void initState() {
    super.initState();
    // Use post frame callback to ensure context is available
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkExistingVendor();
      _loadCurrencies();
    });
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _passwordConfirmationController.dispose();
    _storeNameController.dispose();
    _descriptionController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _countryController.dispose();
    _zipCodeController.dispose();
    super.dispose();
  }

  Future<void> _checkExistingVendor() async {
    final authProvider = context.read<AuthProvider>();
    if (authProvider.isAuthenticated) {
      setState(() {
        _nameController.text = authProvider.user?.name ?? '';
        _emailController.text = authProvider.user?.email ?? '';
      });

      final hasVendor = await context.read<VendorProvider>().checkUserHasVendor();
      if (hasVendor) {
        _showMessage('Vous avez déjà un profil vendeur', Colors.orange);
        Future.delayed(const Duration(seconds: 2), () {
          if (mounted) context.go('/vendors');
        });
      }
    }
  }

  Future<void> _loadCurrencies() async {
    try {
      logDebug('🔵 Loading currencies...');
      // Use context.read since ApiService is now provided
      final apiService = context.read<ApiService>();
      final response = await apiService.getCurrencies();
      logDebug('🔵 Response success: ${response.success}');
      
      if (response.success && response.data != null) {
        final responseData = response.data;
        List<dynamic> currenciesList = [];
        
        if (responseData is List) {
          currenciesList = responseData;
        } else if (responseData is Map<String, dynamic>) {
          if (responseData.containsKey('data')) {
            final dataField = responseData['data'];
            if (dataField is List) {
              currenciesList = dataField;
            }
          }
        }
        
        logDebug('🔵 Currencies loaded: ${currenciesList.length}');
        
        setState(() {
          _currencies = currenciesList.map((e) => Map<String, dynamic>.from(e)).toList();
          if (_currencies.isNotEmpty) {
            _selectedCurrencyId = _currencies.first['id'];
          }
        });
      }
    } catch (e) {
      logDebug('🔴 Error loading currencies: $e');
    }
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1024,
      maxHeight: 1024,
      imageQuality: 85,
    );

    if (pickedFile != null) {
      setState(() {
        _logoFile = File(pickedFile.path);
        _logoPath = pickedFile.path;
      });
    }
  }

  void _acceptTerms() {
    if (!_acceptedTerms) {
      _showMessage('Vous devez accepter les conditions pour continuer', Colors.red);
      return;
    }
    setState(() => _showTermsModal = false);
  }

  Future<void> _submitApplication() async {
    if (!_acceptedTerms) {
      setState(() => _showTermsModal = true);
      _showMessage('Vous devez accepter les conditions pour continuer', Colors.red);
      return;
    }

    if (!_validateForm()) return;

    setState(() => _isLoading = true);

    try {
      final authProvider = context.read<AuthProvider>();
      final apiService = context.read<ApiService>();
      
      // Debug: Check authentication status
      logDebug('🔐 Auth status - isAuthenticated: ${authProvider.isAuthenticated}');
      logDebug('🔐 Auth status - user: ${authProvider.user?.email}');
      logDebug('🔐 Auth status - token: ${authProvider.token != null ? 'Present' : 'Missing'}');

      Map<String, dynamic> applicationData = {
        'store_name': _storeNameController.text.trim(),
        'description': _descriptionController.text.trim(),
        'currency_id': _selectedCurrencyId,
        'address': _addressController.text.trim(),
        'city': _cityController.text.trim(),
        'state': _stateController.text.trim(),
        'country': _countryController.text.trim(),
        'zip_code': _zipCodeController.text.trim(),
      };

      if (!authProvider.isAuthenticated) {
        logDebug('👤 User is NOT authenticated - including user fields');
        applicationData.addAll({
          'name': _nameController.text.trim(),
          'email': _emailController.text.trim(),
          'password': _passwordController.text,
          'password_confirmation': _passwordConfirmationController.text,
        });
      } else {
        logDebug('👤 User IS authenticated - NOT including user fields');
      }

      logDebug('📤 Sending application data: $applicationData');
    
      final response = await apiService.submitVendorApplication(
        applicationData,
        //logoFile: _logoFile,
      );

      logDebug('📥 Response: ${response.data}');


      if (response.success) {
        _showMessage(
          'Candidature soumise avec succès! Votre compte vendeur est en attente d\'approbation.',
          Colors.green,
        );
        Future.delayed(const Duration(seconds: 2), () {
          if (mounted) context.go('/vendors');
        });
      } else {
        _showMessage(response.message ?? 'Une erreur est survenue', Colors.red);
      }
    } catch (e) {
      _showMessage('Erreur: ${e.toString()}', Colors.red);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  bool _validateForm() {
    final authProvider = context.read<AuthProvider>();
    
    if (!authProvider.isAuthenticated) {
      if (_nameController.text.isEmpty) {
        _showMessage('Le nom est requis', Colors.red);
        return false;
      }
      if (_emailController.text.isEmpty || !_emailController.text.contains('@')) {
        _showMessage('Email invalide', Colors.red);
        return false;
      }
      if (_passwordController.text.length < 6) {
        _showMessage('Le mot de passe doit contenir au moins 6 caractères', Colors.red);
        return false;
      }
      if (_passwordController.text != _passwordConfirmationController.text) {
        _showMessage('Les mots de passe ne correspondent pas', Colors.red);
        return false;
      }
    }

    if (_storeNameController.text.isEmpty) {
      _showMessage('Le nom de la boutique est requis', Colors.red);
      return false;
    }
    if (_selectedCurrencyId == null) {
      _showMessage('La devise est requise', Colors.red);
      return false;
    }

    return true;
  }

  void _showMessage(String message, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final isLoggedIn = authProvider.isAuthenticated;
    final user = authProvider.user;
    final isAdmin = user?.roles.contains('admin') ?? false;
    final isVendor = user?.roles.contains('vendor') ?? false;

    if (isAdmin || isVendor) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) {
            String message = isAdmin 
                ? 'Les administrateurs ne peuvent pas devenir vendeurs'
                : 'Vous avez déjà un profil vendeur';
            _showMessage(message, Colors.orange);
            Future.delayed(const Duration(seconds: 2), () {
              if (mounted) context.go('/vendors');
            });
          }
        });
      }
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Devenir Vendeur', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1F2937))),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => context.go('/vendors'),
        ),
      ),
      body: Stack(
        children: [
          Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 600),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildHeader(),
                    const SizedBox(height: 16),
                    if (!isLoggedIn) _buildUserAccountSection(),
                    _buildVendorProfileSection(),
                    const SizedBox(height: 16),
                    _buildAddressSection(),
                    const SizedBox(height: 24),
                    _buildSubmitButton(),
                  ],
                ),
              ),
            ),
          ),
          if (_showTermsModal) _buildTermsModal(),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.1), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Candidature Vendeur', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF1F2937))),
          const SizedBox(height: 8),
          Text('Veuillez lire et accepter les conditions avant de soumettre votre profil vendeur.',
              style: TextStyle(fontSize: 14, color: Colors.grey[600])),
        ],
      ),
    );
  }

  Widget _buildUserAccountSection() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.1), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Créer votre compte', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          _buildTextField(controller: _nameController, label: 'Nom complet', icon: Icons.person_outline),
          const SizedBox(height: 12),
          _buildTextField(controller: _emailController, label: 'Email', icon: Icons.email_outlined, keyboardType: TextInputType.emailAddress),
          const SizedBox(height: 12),
          _buildPasswordField(controller: _passwordController, label: 'Mot de passe', obscure: _obscurePassword,
              onToggle: () => setState(() => _obscurePassword = !_obscurePassword)),
          const SizedBox(height: 12),
          _buildPasswordField(controller: _passwordConfirmationController, label: 'Confirmer le mot de passe',
              obscure: _obscureConfirmPassword, onToggle: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword)),
        ],
      ),
    );
  }

  Widget _buildVendorProfileSection() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.1), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Profil Vendeur', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          _buildTextField(controller: _storeNameController, label: 'Nom de la boutique', icon: Icons.store_outlined, required: true),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            initialValue: _selectedCurrencyId,
            decoration: InputDecoration(
              labelText: 'Devise *',
              prefixIcon: const Icon(Icons.attach_money, color: Color(0xFFD4AF37)),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[300]!)),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[300]!)),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFD4AF37), width: 2)),
              filled: true,
              fillColor: Colors.grey[50],
            ),
            items: _currencies.map((currency) {
              return DropdownMenuItem<int>(
                value: currency['id'],
                child: Text('${currency['code']} - ${currency['name']}'),
              );
            }).toList(),
            onChanged: (value) => setState(() => _selectedCurrencyId = value),
          ),
          const SizedBox(height: 12),
          _buildTextField(controller: _descriptionController, label: 'Description', icon: Icons.description_outlined, maxLines: 4),
          const SizedBox(height: 12),
          _buildLogoUploadSection(),
        ],
      ),
    );
  }

  Widget _buildLogoUploadSection() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.grey[300]!)),
      child: Row(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: Colors.grey[200],
              borderRadius: BorderRadius.circular(8),
              image: _logoFile != null ? DecorationImage(image: FileImage(_logoFile!), fit: BoxFit.cover) : null,
            ),
            child: _logoFile == null ? const Icon(Icons.image, color: Colors.grey, size: 30) : null,
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Logo de la boutique', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 4),
                Text('PNG, JPG, WEBP - max 2MB', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: _pickImage,
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Choisir'),
          ),
        ],
      ),
    );
  }

  Widget _buildAddressSection() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.1), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Adresse', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          _buildTextField(controller: _addressController, label: 'Adresse', icon: Icons.location_on_outlined),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _buildTextField(controller: _cityController, label: 'Ville')),
              const SizedBox(width: 12),
              Expanded(child: _buildTextField(controller: _stateController, label: 'Région')),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _buildTextField(controller: _countryController, label: 'Pays')),
              const SizedBox(width: 12),
              Expanded(child: _buildTextField(controller: _zipCodeController, label: 'Code postal')),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSubmitButton() {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 20),
      child: ElevatedButton(
        onPressed: _isLoading ? null : _submitApplication,
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFD4AF37),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          disabledBackgroundColor: Colors.grey[300],
        ),
        child: _isLoading
            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, valueColor: AlwaysStoppedAnimation<Color>(Colors.white)))
            : const Text('Soumettre ma candidature', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      ),
    );
  }

  Widget _buildTermsModal() {
    return Container(
      color: Colors.black54,
      child: Center(
        child: TermsModal(
          acceptedTerms: _acceptedTerms,
          onTermsChanged: (value) => setState(() => _acceptedTerms = value ?? false),
          onAccept: _acceptTerms,
          onCancel: () => context.go('/vendors'),
          pdfUrl: 'assets/CommissionContract.pdf',//'afrobridgeinnov.com/assets/CommissionContract.pdf',
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    IconData? icon,
    TextInputType? keyboardType,
    int maxLines = 1,
    bool required = false,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: required ? '$label *' : label,
        prefixIcon: icon != null ? Icon(icon, color: const Color(0xFFD4AF37)) : null,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[300]!)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[300]!)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFD4AF37), width: 2)),
        filled: true,
        fillColor: Colors.grey[50],
      ),
    );
  }

  Widget _buildPasswordField({
    required TextEditingController controller,
    required String label,
    required bool obscure,
    required VoidCallback onToggle,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: obscure,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.lock_outline, color: Color(0xFFD4AF37)),
        suffixIcon: IconButton(
          icon: Icon(obscure ? Icons.visibility_off : Icons.visibility, color: Colors.grey),
          onPressed: onToggle,
        ),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[300]!)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey[300]!)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFD4AF37), width: 2)),
        filled: true,
        fillColor: Colors.grey[50],
      ),
    );
  }
}