// lib/screens/checkout/checkout_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../core/providers/checkout_provider.dart';
import '../../core/providers/cart_provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/models/checkout_models.dart';
import '../../core/models/order.dart';
import 'package:url_launcher/url_launcher.dart';

class CheckoutScreen extends StatefulWidget {
  final List<int> selectedIds;
  const CheckoutScreen({super.key, required this.selectedIds});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _formKey = GlobalKey<FormState>();
  
  // Address form controllers
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _streetController = TextEditingController();
  final _cityController = TextEditingController();
  final _stateController = TextEditingController();
  final _zipController = TextEditingController();
  String _country = 'Guinea';

  @override
  void initState() {
    super.initState();
    // Set selected IDs in provider
    WidgetsBinding.instance.addPostFrameCallback((_) {
    final checkoutProvider = context.read<CheckoutProvider>();
    checkoutProvider.setSelectedIds(widget.selectedIds);
    
    // Load saved addresses if authenticated
      if (context.read<AuthProvider>().isAuthenticated) {
        checkoutProvider.loadSavedAddresses();
      }
    });
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    _streetController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _zipController.dispose();
    super.dispose();
  }

  void _updateShippingAddressFromForm() {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<CheckoutProvider>();
    final address = ShippingAddress(
      firstName: _firstNameController.text,
      lastName: _lastNameController.text,
      phone: _phoneController.text,
      streetAddress: _streetController.text,
      city: _cityController.text,
      state: _stateController.text,
      zipCode: _zipController.text,
      country: _country,
      latitude: provider.latitude,
      longitude: provider.longitude,
    );
    
    provider.updateShippingAddress(address);
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final cartProvider = context.watch<CartProvider>();
    final checkoutProvider = context.watch<CheckoutProvider>();
    final isAuthenticated = authProvider.isAuthenticated;

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Checkout'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Saved Addresses (if logged in)
              if (isAuthenticated && checkoutProvider.savedAddresses.isNotEmpty)
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.1),
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
                            'Saved Addresses',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              checkoutProvider.clearSelectedAddress();
                              _clearForm();
                            },
                            child: const Text('Use new address'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      
                      // FIXED: Address dropdown with proper overflow handling
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey[300]!),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            value: checkoutProvider.selectedAddress?.id,
                            hint: const Text('Select an address'),
                            isExpanded: true,
                            icon: const Icon(Icons.arrow_drop_down),
                            items: [
                              const DropdownMenuItem<int?>(
                                value: null,
                                child: Text('Select an address'),
                              ),
                              ...checkoutProvider.savedAddresses.map((addr) {
                                // Create a shortened address string
                                String addressText = addr.fullName;
                                if (addr.streetAddress.isNotEmpty) {
                                  addressText += ' — ${addr.streetAddress.substring(0, addr.streetAddress.length > 20 ? 20 : addr.streetAddress.length)}${addr.streetAddress.length > 20 ? '...' : ''}';
                                }
                                if (addr.city.isNotEmpty) {
                                  addressText += ', ${addr.city}';
                                }
                                if (addr.isDefault) {
                                  addressText += ' (Default)';
                                }
                                
                                return DropdownMenuItem<int?>(
                                  value: addr.id,
                                  child: Tooltip(
                                    message: '${addr.fullName} — ${addr.streetAddress}, ${addr.city}, ${addr.state} ${addr.zipCode}, ${addr.country}',
                                    child: Text(
                                      addressText,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                );
                              }),
                            ],
                            onChanged: (value) {
                              if (value != null) {
                                final selected = checkoutProvider.savedAddresses.firstWhere((a) => a.id == value);
                                checkoutProvider.selectAddress(selected);
                                _fillFormWithAddress(selected);
                              }
                            },
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

              // Shipping Address Form
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withOpacity(0.1),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Delivery Address',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 16),

                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _firstNameController,
                            decoration: const InputDecoration(
                              labelText: 'First Name *',
                              border: OutlineInputBorder(),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Required';
                              }
                              return null;
                            },
                            onChanged: (_) => _updateShippingAddressFromForm(),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _lastNameController,
                            decoration: const InputDecoration(
                              labelText: 'Last Name *',
                              border: OutlineInputBorder(),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Required';
                              }
                              return null;
                            },
                            onChanged: (_) => _updateShippingAddressFromForm(),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 12),

                    TextFormField(
                      controller: _phoneController,
                      decoration: const InputDecoration(
                        labelText: 'Phone *',
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.phone,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Required';
                        }
                        return null;
                      },
                      onChanged: (_) => _updateShippingAddressFromForm(),
                    ),

                    const SizedBox(height: 12),

                    TextFormField(
                      controller: _streetController,
                      decoration: const InputDecoration(
                        labelText: 'Street Address *',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 2,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Required';
                        }
                        return null;
                      },
                      onChanged: (_) => _updateShippingAddressFromForm(),
                    ),

                    const SizedBox(height: 12),

                    // Replace the Row with city, state, zip with this new layout:

                    // City and State row
                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: TextFormField(
                            controller: _cityController,
                            decoration: const InputDecoration(
                              labelText: 'City *',
                              border: OutlineInputBorder(),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Required';
                              }
                              return null;
                            },
                            onChanged: (_) => _updateShippingAddressFromForm(),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _stateController,
                            decoration: const InputDecoration(
                              labelText: 'State *',
                              border: OutlineInputBorder(),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Required';
                              }
                              return null;
                            },
                            onChanged: (_) => _updateShippingAddressFromForm(),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 12),

                    // Country and Zip row (zip after country)
                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: DropdownButtonFormField<String>(
                            initialValue: _country,
                            decoration: const InputDecoration(
                              labelText: 'Country *',
                              border: OutlineInputBorder(),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'Guinea', child: Text('Guinea')),
                              DropdownMenuItem(value: 'Senegal', child: Text('Senegal')),
                              DropdownMenuItem(value: 'Ivory Coast', child: Text('Ivory Coast')),
                              DropdownMenuItem(value: 'Mali', child: Text('Mali')),
                              DropdownMenuItem(value: 'France', child: Text('France')),
                              DropdownMenuItem(value: 'United States', child: Text('United States')),
                              DropdownMenuItem(value: 'Canada', child: Text('Canada')),
                              DropdownMenuItem(value: 'United Kingdom', child: Text('United Kingdom')),
                            ],
                            onChanged: (value) {
                              setState(() {
                                _country = value!;
                              });
                              _updateShippingAddressFromForm();
                            },
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _zipController,
                            decoration: const InputDecoration(
                              labelText: 'Zip Code *',
                              border: OutlineInputBorder(),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Required';
                              }
                              return null;
                            },
                            onChanged: (_) => _updateShippingAddressFromForm(),
                          ),
                        ),
                      ],
                    ),
                    
                    const SizedBox(height: 16),

                    // Geolocation
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Location (Optional)',
                                style: TextStyle(fontWeight: FontWeight.w600),
                              ),
                              ElevatedButton(
                                onPressed: checkoutProvider.isGettingLocation
                                    ? null
                                    : () => checkoutProvider.getLocation(),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.blue[100],
                                  foregroundColor: Colors.blue[800],
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                ),
                                child: checkoutProvider.isGettingLocation
                                    ? const SizedBox(
                                        height: 16,
                                        width: 16,
                                        child: CircularProgressIndicator(strokeWidth: 2),
                                      )
                                    : const Text('📍 Get My Location'),
                              ),
                            ],
                          ),
                          if (checkoutProvider.latitude != null && checkoutProvider.longitude != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 8),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      'Lat: ${checkoutProvider.latitude!.toStringAsFixed(6)}',
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                  Expanded(
                                    child: Text(
                                      'Lng: ${checkoutProvider.longitude!.toStringAsFixed(6)}',
                                      style: const TextStyle(fontSize: 12),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Save address checkbox (for logged in users)
                    if (isAuthenticated)
                      Row(
                        children: [
                          Checkbox(
                            value: checkoutProvider.saveAddress,
                            onChanged: (value) {
                              if (value != null) {
                                checkoutProvider.setSaveAddress(value);
                              }
                            },
                            activeColor: const Color(0xFFD4AF37),
                          ),
                          const Text('Save this address to my profile'),
                        ],
                      ),

                    const SizedBox(height: 16),

                    // Calculate Shipping Button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: checkoutProvider.isCalculatingShipping
                            ? null
                            : () async {
                                if (_formKey.currentState!.validate()) {
                                  _updateShippingAddressFromForm();
                                  await checkoutProvider.calculateShipping();
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue[600],
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: checkoutProvider.isCalculatingShipping
                            ? const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  ),
                                  SizedBox(width: 12),
                                  Text('Calculating Shipping...'),
                                ],
                              )
                            : const Text('🚚 Calculate Shipping Costs'),
                      ),
                    ),

                    if (checkoutProvider.shippingError != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          checkoutProvider.shippingError!,
                          style: const TextStyle(color: Colors.red, fontSize: 12),
                        ),
                      ),
                  ],
                ),
              ),

              // Shipping Options - FIXED OVERFLOW
              if (checkoutProvider.hasShippingCalculated &&
                  checkoutProvider.shippingResult != null &&
                  checkoutProvider.shippingResult!.carriers.isNotEmpty)
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.1),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Shipping Options',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      ...checkoutProvider.shippingResult!.carriers.entries.map((entry) {
                        final carrier = entry.value;
                        final isSelected = checkoutProvider.selectedCarrier == entry.key;
                        
                        return Container(
                          margin: const EdgeInsets.only(bottom: 6),
                          decoration: BoxDecoration(
                            border: Border.all(
                              color: isSelected
                                  ? const Color(0xFFD4AF37)
                                  : Colors.grey[300]!,
                              width: isSelected ? 2 : 1,
                            ),
                            borderRadius: BorderRadius.circular(6),
                            color: isSelected ? const Color(0xFFD4AF37).withOpacity(0.05) : null,
                          ),
                          child: InkWell(
                            onTap: () => checkoutProvider.selectCarrier(entry.key),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                              child: Row(
                                children: [
                                  // Radio indicator - smaller
                                  Container(
                                    width: 16,
                                    height: 16,
                                    decoration: BoxDecoration(
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: isSelected ? const Color(0xFFD4AF37) : Colors.grey[400]!,
                                        width: 2,
                                      ),
                                    ),
                                    child: isSelected
                                        ? Center(
                                            child: Container(
                                              width: 8,
                                              height: 8,
                                              decoration: const BoxDecoration(
                                                shape: BoxShape.circle,
                                                color: Color(0xFFD4AF37),
                                              ),
                                            ),
                                          )
                                        : null,
                                  ),
                                  const SizedBox(width: 8),
                                  
                                  // Carrier details - Expanded to take available space
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          carrier.name,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w600,
                                            fontSize: 13,
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        if (carrier.description != null)
                                          Text(
                                            carrier.description!,
                                            style: TextStyle(
                                              fontSize: 10,
                                              color: Colors.grey[600],
                                            ),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        // Show vendor zones - more compact
                                        if (carrier.vendors.isNotEmpty)
                                          Wrap(
                                            spacing: 2,
                                            children: carrier.vendors.values.take(2).map((v) {
                                              return Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 3, vertical: 1),
                                                decoration: BoxDecoration(
                                                  color: Colors.grey[100],
                                                  borderRadius: BorderRadius.circular(3),
                                                ),
                                                child: Text(
                                                  '${v.zone}: ${v.deliveryDays}d',
                                                  style: const TextStyle(fontSize: 8),
                                                ),
                                              );
                                            }).toList(),
                                          ),
                                      ],
                                    ),
                                  ),
                                  
                                  // Price - Fixed width
                                  Container(
                                    width: 60,
                                    padding: const EdgeInsets.only(left: 4),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        Text(
                                          '\$${carrier.totalCostUsd.toStringAsFixed(2)}',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 12,
                                            color: Color(0xFFD4AF37),
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        const Text(
                                          'Shipping',
                                          style: TextStyle(fontSize: 8),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        );
                      }),
                    ],
                  ),
                ),

              // Payment Methods - FIXED OVERFLOW
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withOpacity(0.1),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Payment Method *',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    
                    if (checkoutProvider.hasMultipleVendors)
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 12),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.yellow[50],
                          border: Border.all(color: Colors.yellow[300]!),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.warning, color: Colors.amber, size: 20),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Multiple Vendors: Only Cash on Delivery',
                                style: TextStyle(color: Colors.amber[800], fontSize: 12),
                              ),
                            ),
                          ],
                        ),
                      ),

                    const SizedBox(height: 12),

                    // Payment options in a row with proper sizing
                    Row(
                      children: [
                        Expanded(
                          child: _buildPaymentOption(
                            value: 'cod',
                            label: '💵 Cash',
                            subtitle: 'On Delivery',
                            enabled: true,
                            isSelected: checkoutProvider.paymentMethod == 'cod',
                            onTap: () => checkoutProvider.setPaymentMethod('cod'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _buildPaymentOption(
                            value: 'stripe',
                            label: '💳 Card',
                            subtitle: 'LengoPay',
                            enabled: !checkoutProvider.hasMultipleVendors,
                            isSelected: checkoutProvider.paymentMethod == 'stripe',
                            onTap: () => checkoutProvider.setPaymentMethod('stripe'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Order Summary
              if (checkoutProvider.summary != null)
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.1),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Order Summary',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Display by vendor
                      ...checkoutProvider.summary!.vendors.map((vendor) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    '🏬 ${vendor.vendorName}',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  if (vendor.zone != 'Unknown')
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: Colors.blue[100],
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        vendor.zone,
                                        style: const TextStyle(fontSize: 10),
                                      ),
                                    ),
                                ],
                              ),
                              const SizedBox(height: 8),

                              // Items list
                              ...vendor.items.map((item) {
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Row(
                                    children: [
                                      // Product image placeholder
                                      Container(
                                        width: 40,
                                        height: 40,
                                        decoration: BoxDecoration(
                                          color: Colors.grey[200],
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: const Icon(Icons.image, size: 20, color: Colors.grey),
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              item.productName,
                                              style: const TextStyle(
                                                fontSize: 12,
                                                fontWeight: FontWeight.w500,
                                              ),
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                            if (item.variationNote.isNotEmpty)
                                              Text(
                                                item.variationNote,
                                                style: TextStyle(
                                                  fontSize: 10,
                                                  color: Colors.grey[600],
                                                ),
                                              ),
                                            Text(
                                              '× ${item.quantity}',
                                              style: TextStyle(
                                                fontSize: 10,
                                                color: Colors.grey[500],
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Column(
                                        crossAxisAlignment: CrossAxisAlignment.end,
                                        children: [
                                          Text(
                                            '${item.currency} ${item.unitAmount.toStringAsFixed(2)}',
                                            style: const TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                          Text(
                                            '${item.currency} ${item.totalAmount.toStringAsFixed(2)}',
                                            style: TextStyle(
                                              fontSize: 10,
                                              color: Colors.grey[600],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                );
                              }),
                              const Divider(height: 12),

                              // Vendor totals
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'Subtotal:',
                                    style: TextStyle(fontSize: 12),
                                  ),
                                  Text(
                                    '${vendor.currency} ${vendor.subtotal.toStringAsFixed(2)}',
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 2),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'Shipping:',
                                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                                  ),
                                  Text(
                                    '${vendor.currency} ${vendor.shipping.toStringAsFixed(2)}',
                                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 2),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'Total:',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  Text(
                                    '${vendor.currency} ${vendor.total.toStringAsFixed(2)}',
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      color: Color(0xFFD4AF37),
                                    ),
                                  ),
                                ],
                              ),

                              if (vendor != checkoutProvider.summary!.vendors.last)
                                const Divider(height: 20),
                            ],
                          ),
                        );
                      }),

                      const Divider(height: 12),

                      // Grand total
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Subtotal USD:',
                            style: TextStyle(fontSize: 13),
                          ),
                          Text(
                            '\$${checkoutProvider.summary!.subtotalUsd.toStringAsFixed(2)}',
                            style: const TextStyle(fontSize: 13),
                          ),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Shipping USD:',
                            style: TextStyle(fontSize: 13),
                          ),
                          Text(
                            '\$${checkoutProvider.summary!.totalShippingUsd.toStringAsFixed(2)}',
                            style: const TextStyle(fontSize: 13),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Total USD:',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            '\$${checkoutProvider.summary!.grandTotalUsd.toStringAsFixed(2)}',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFFD4AF37),
                            ),
                          ),
                        ],
                      ),

                      if (checkoutProvider.hasShippingCalculated)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: const Text(
                            '✅ Shipping calculated',
                            style: TextStyle(
                              fontSize: 10,
                              color: Colors.green,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),

              // Place Order Button
              // Place Order Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: checkoutProvider.isPlacingOrder
                      ? null
                      : () async {
                          if (_formKey.currentState!.validate()) {
                            _updateShippingAddressFromForm();
                            final success = await checkoutProvider.placeOrder();
                            
                            if (success && mounted) {
                              final redirectUrl = checkoutProvider.lastOrderResponse?.redirectUrl;
                              
                              if (redirectUrl != null && redirectUrl.isNotEmpty) {
                                // 🔥 IMPORTANT: Open the payment URL in browser
                                final Uri url = Uri.parse(redirectUrl);
                                if (await canLaunchUrl(url)) {
                                  await launchUrl(url, mode: LaunchMode.externalApplication);
                                } else {
                                  // Fallback
                                  if (mounted) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text('Could not open payment page'),
                                        backgroundColor: Colors.orange,
                                      ),
                                    );
                                  }
                                }
                              } else {
                                // For COD/cash payments, go directly to success page
                                /* final placedOrders = checkoutProvider.placedOrders;
                                context.go(
                                  '/payment/success',
                                  extra: {
                                    'orders': placedOrders,
                                    'total_amount': checkoutProvider.summary?.grandTotalUsd ?? 0,
                                    'order_count': placedOrders.length,
                                  },
                                ); */
                                context.go('/payment/success');
                              }
                            }else if (!success && mounted) {
                              context.go('/payment/cancel?reason=${Uri.encodeComponent(checkoutProvider.orderError ?? "Unknown error")}');
                            }else if (checkoutProvider.orderError != null) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(checkoutProvider.orderError!),
                                  backgroundColor: Colors.red,
                                ),
                              );
                            }
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD4AF37),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    disabledBackgroundColor: Colors.grey[300],
                  ),
                  child: checkoutProvider.isPlacingOrder
                      ? const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            ),
                            SizedBox(width: 8),
                            Text('Processing Order...'),
                          ],
                        )
                      : Text(
                          'Place Order (${widget.selectedIds.length} items)',
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),
                            
              if (checkoutProvider.orderError != null)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(
                    checkoutProvider.orderError!,
                    style: const TextStyle(color: Colors.red, fontSize: 12),
                    textAlign: TextAlign.center,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPaymentOption({
    required String value,
    required String label,
    required String subtitle,
    required bool enabled,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: enabled ? onTap : null,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        decoration: BoxDecoration(
          color: enabled
              ? (isSelected ? Colors.yellow[50] : Colors.white)
              : Colors.grey[100],
          border: Border.all(
            color: isSelected && enabled
                ? const Color(0xFFD4AF37)
                : Colors.grey[300]!,
            width: isSelected && enabled ? 2 : 1,
          ),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 12,
                color: enabled ? Colors.black87 : Colors.grey[500],
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 2),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: 9,
                color: enabled ? Colors.grey[600] : Colors.grey[400],
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  void _fillFormWithAddress(Address address) {
    _firstNameController.text = address.firstName;
    _lastNameController.text = address.lastName;
    _phoneController.text = address.phone;
    _streetController.text = address.streetAddress;
    _cityController.text = address.city;
    _stateController.text = address.state;
    _zipController.text = address.zipCode;
    _country = address.country;
    
    _updateShippingAddressFromForm();
  }

  void _clearForm() {
    _firstNameController.clear();
    _lastNameController.clear();
    _phoneController.clear();
    _streetController.clear();
    _cityController.clear();
    _stateController.clear();
    _zipController.clear();
    _country = 'Guinea';
  }
}