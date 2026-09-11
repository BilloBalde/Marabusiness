// lib/screens/profile/edit_profile_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/models/user.dart';
import '../../widgets/common/loading_widget.dart';

class EditProfileScreen extends StatefulWidget {
  final User user;

  const EditProfileScreen({super.key, required this.user});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _emailController;
  late TextEditingController _phoneController;
  late TextEditingController _currentPasswordController;
  late TextEditingController _newPasswordController;
  late TextEditingController _confirmPasswordController;
  
  final bool _isLoading = false;
  bool _isSaving = false;
  bool _showPasswordFields = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initializeControllers();
  }

  void _initializeControllers() {
    _nameController = TextEditingController(text: widget.user.name);
    _emailController = TextEditingController(text: widget.user.email);
    _phoneController = TextEditingController(text: widget.user.phone ?? '');
    _currentPasswordController = TextEditingController();
    _newPasswordController = TextEditingController();
    _confirmPasswordController = TextEditingController();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSaving = true;
      _error = null;
    });

    try {
      final Map<String, dynamic> updateData = {
        'name': _nameController.text.trim(),
        'email': _emailController.text.trim(),
        'phone': _phoneController.text.trim(),
      };

      // Add password if changing
      if (_showPasswordFields && _newPasswordController.text.isNotEmpty) {
        updateData['current_password'] = _currentPasswordController.text;
        updateData['new_password'] = _newPasswordController.text;
        updateData['new_password_confirmation'] = _confirmPasswordController.text;
      }

      final authProvider = context.read<AuthProvider>();
      final success = await authProvider.updateProfile(updateData);

      if (success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Profil mis à jour avec succès'),
            backgroundColor: Colors.green,
          ),
        );
        context.pop();
      } else if (mounted) {
        setState(() {
          _error = authProvider.error ?? 'Erreur lors de la mise à jour';
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _isSaving = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final canEdit = !widget.user.roles.contains('vendor') && 
                    !widget.user.roles.contains('manager');

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Modifier le profil'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
      ),
      body: _isLoading
          ? const LoadingWidget(message: 'Chargement...')
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  children: [
                    // Profile Info Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.grey.withValues(alpha: 0.1),
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Informations personnelles',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 16),
                          
                          // Name
                          TextFormField(
                            controller: _nameController,
                            enabled: canEdit,
                            decoration: InputDecoration(
                              labelText: 'Nom complet *',
                              border: const OutlineInputBorder(),
                              filled: true,
                              fillColor: canEdit ? Colors.white : Colors.grey[100],
                              prefixIcon: const Icon(Icons.person_outline),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Requis';
                              }
                              return null;
                            },
                          ),
                          
                          const SizedBox(height: 12),
                          
                          // Email
                          TextFormField(
                            controller: _emailController,
                            enabled: canEdit,
                            decoration: InputDecoration(
                              labelText: 'Email *',
                              border: const OutlineInputBorder(),
                              filled: true,
                              fillColor: canEdit ? Colors.white : Colors.grey[100],
                              prefixIcon: const Icon(Icons.email_outlined),
                            ),
                            keyboardType: TextInputType.emailAddress,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Requis';
                              }
                              if (!value.contains('@')) {
                                return 'Email invalide';
                              }
                              return null;
                            },
                          ),
                          
                          const SizedBox(height: 12),
                          
                          // Phone
                          TextFormField(
                            controller: _phoneController,
                            enabled: canEdit,
                            decoration: InputDecoration(
                              labelText: 'Téléphone',
                              border: const OutlineInputBorder(),
                              filled: true,
                              fillColor: canEdit ? Colors.white : Colors.grey[100],
                              prefixIcon: const Icon(Icons.phone_outlined),
                            ),
                            keyboardType: TextInputType.phone,
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Password Change Section (only if canEdit)
                    if (canEdit)
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.grey.withValues(alpha: 0.1),
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text(
                                  'Changer le mot de passe',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                Switch(
                                  value: _showPasswordFields,
                                  onChanged: (value) {
                                    setState(() {
                                      _showPasswordFields = value;
                                      if (!value) {
                                        _currentPasswordController.clear();
                                        _newPasswordController.clear();
                                        _confirmPasswordController.clear();
                                      }
                                    });
                                  },
                                  activeThumbColor: const Color(0xFFD4AF37),
                                ),
                              ],
                            ),
                            
                            if (_showPasswordFields) ...[
                              const SizedBox(height: 16),
                              
                              // Current Password
                              TextFormField(
                                controller: _currentPasswordController,
                                obscureText: true,
                                decoration: const InputDecoration(
                                  labelText: 'Mot de passe actuel *',
                                  border: OutlineInputBorder(),
                                  filled: true,
                                  fillColor: Colors.white,
                                  prefixIcon: Icon(Icons.lock_outline),
                                ),
                                validator: (value) {
                                  if (_showPasswordFields && (value == null || value.isEmpty)) {
                                    return 'Requis';
                                  }
                                  return null;
                                },
                              ),
                              
                              const SizedBox(height: 12),
                              
                              // New Password
                              TextFormField(
                                controller: _newPasswordController,
                                obscureText: true,
                                decoration: const InputDecoration(
                                  labelText: 'Nouveau mot de passe *',
                                  border: OutlineInputBorder(),
                                  filled: true,
                                  fillColor: Colors.white,
                                  prefixIcon: Icon(Icons.lock_outline),
                                ),
                                validator: (value) {
                                  if (_showPasswordFields) {
                                    if (value == null || value.isEmpty) {
                                      return 'Requis';
                                    }
                                    if (value.length < 6) {
                                      return 'Minimum 6 caractères';
                                    }
                                  }
                                  return null;
                                },
                              ),
                              
                              const SizedBox(height: 12),
                              
                              // Confirm Password
                              TextFormField(
                                controller: _confirmPasswordController,
                                obscureText: true,
                                decoration: const InputDecoration(
                                  labelText: 'Confirmer le mot de passe *',
                                  border: OutlineInputBorder(),
                                  filled: true,
                                  fillColor: Colors.white,
                                  prefixIcon: Icon(Icons.lock_outline),
                                ),
                                validator: (value) {
                                  if (_showPasswordFields) {
                                    if (value == null || value.isEmpty) {
                                      return 'Requis';
                                    }
                                    if (value != _newPasswordController.text) {
                                      return 'Les mots de passe ne correspondent pas';
                                    }
                                  }
                                  return null;
                                },
                              ),
                            ],
                          ],
                        ),
                      ),

                    if (_error != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 16),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.red[50],
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.red[200]!),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.error_outline, color: Colors.red[700], size: 20),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  _error!,
                                  style: TextStyle(color: Colors.red[700]),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                    const SizedBox(height: 24),

                    // Buttons
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: _isSaving ? null : () => context.pop(),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: Colors.black87,
                              side: const BorderSide(color: Colors.grey),
                              padding: const EdgeInsets.symmetric(vertical: 16),
                            ),
                            child: const Text('Annuler'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: (canEdit && !_isSaving) ? _saveProfile : null,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFFD4AF37),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                            ),
                            child: _isSaving
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Text('Enregistrer'),
                          ),
                        ),
                      ],
                    ),

                    if (!canEdit)
                      Padding(
                        padding: const EdgeInsets.only(top: 16),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.amber[50],
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.amber[200]!),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.info_outline, color: Colors.amber, size: 20),
                              SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'Les comptes administrateurs et vendeurs ne peuvent pas modifier leur profil. Contactez le support pour toute modification.',
                                  style: TextStyle(color: Colors.amber),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
    );
  }
}