// lib/screens/orders/orders_screen.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../core/providers/order_provider.dart';
import '../../core/models/order.dart';
import '../../utils/currency_formatter.dart';
import '../../widgets/common/loading_widget.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> with SingleTickerProviderStateMixin {
  final TextEditingController _searchController = TextEditingController();
  late TabController _statusTabController;
  String _selectedStatus = 'all';
  Map<String, int> _statusCounts = {};
  bool _isLoadingCounts = true;

  @override
  void initState() {
    super.initState();
    _statusTabController = TabController(length: 6, vsync: this);
    _statusTabController.addListener(_handleTabChange);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialData();
    });
  }

  @override
  void dispose() {
    _statusTabController.removeListener(_handleTabChange);
    _statusTabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _handleTabChange() {
    if (!_statusTabController.indexIsChanging) {
      final statusIndex = _statusTabController.index;
      final status = _getStatusFromIndex(statusIndex);
      setState(() => _selectedStatus = status);
      context.read<OrderProvider>().filterByStatus(status);
    }
  }

  String _getStatusFromIndex(int index) {
    switch (index) {
      case 0: return 'all';
      case 1: return 'new';
      case 2: return 'processing';
      case 3: return 'shipped';
      case 4: return 'delivered';
      case 5: return 'cancelled';
      default: return 'all';
    }
  }

  Future<void> _loadInitialData() async {
    final orderProvider = context.read<OrderProvider>();
    await orderProvider.loadOrders(refresh: true);
    _loadStatusCounts();
  }

  Future<void> _loadStatusCounts() async {
    setState(() => _isLoadingCounts = true);
    final counts = await context.read<OrderProvider>().getStatusCounts();
    setState(() {
      _statusCounts = counts;
      _isLoadingCounts = false;
    });
  }

  Future<void> _searchOrders() async {
    final query = _searchController.text.trim();
    
    // 🔥 FIX: Reset to first page and clear existing orders when searching
    if (query.isNotEmpty) {
      await context.read<OrderProvider>().searchOrders(query);
    } else {
      // If search is empty, reload all orders
      await context.read<OrderProvider>().loadOrders(refresh: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final orderProvider = context.watch<OrderProvider>();
    final orders = orderProvider.orders;

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Mes Commandes',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        centerTitle: false,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          // 🔥 Add clear search button
          if (_searchController.text.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.clear),
              onPressed: () {
                _searchController.clear();
                _searchOrders();
              },
            ),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.05),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Rechercher par numéro de commande',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                      filled: true,
                      fillColor: Colors.grey[100],
                      prefixIcon: const Icon(Icons.search, color: Colors.grey),
                      // 🔥 Add clear button inside text field
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                _searchOrders();
                              },
                            )
                          : null,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                    onSubmitted: (_) => _searchOrders(),
                  ),
                ),
                const SizedBox(width: 12),
                ElevatedButton(
                  onPressed: _searchOrders,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD4AF37),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text('Rechercher'),
                ),
              ],
            ),
          ),

          // 🔥 Active Search Chip (show when searching)
          if (orderProvider.searchQuery != null && orderProvider.searchQuery!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  const Icon(Icons.search, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  const Text('Recherche:', style: TextStyle(fontSize: 12, color: Colors.grey)),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD4AF37).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          orderProvider.searchQuery!,
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
                        ),
                        const SizedBox(width: 4),
                        GestureDetector(
                          onTap: () {
                            _searchController.clear();
                            _searchOrders();
                          },
                          child: const Icon(Icons.close, size: 12, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

          // Status Filter Tabs
          Container(
            color: Colors.white,
            child: Column(
              children: [
                TabBar(
                  controller: _statusTabController,
                  isScrollable: true,
                  labelColor: const Color(0xFFD4AF37),
                  unselectedLabelColor: Colors.grey,
                  indicatorColor: const Color(0xFFD4AF37),
                  indicatorWeight: 3,
                  tabs: [
                    _buildStatusTab('Tous', _statusCounts['all'] ?? 0, Icons.list_alt),
                    _buildStatusTab('Nouveau', _statusCounts['new'] ?? 0, Icons.access_time),
                    _buildStatusTab('Traitement', _statusCounts['processing'] ?? 0, Icons.settings),
                    _buildStatusTab('Expédié', _statusCounts['shipped'] ?? 0, Icons.local_shipping),
                    _buildStatusTab('Livré', _statusCounts['delivered'] ?? 0, Icons.check_circle),
                    _buildStatusTab('Annulé', _statusCounts['cancelled'] ?? 0, Icons.cancel),
                  ],
                ),
                const Divider(height: 1),
              ],
            ),
          ),

          // Active Filter Chip
          if (_selectedStatus != 'all')
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  const Icon(Icons.filter_alt, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  const Text('Filtré par:', style: TextStyle(fontSize: 12, color: Colors.grey)),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _getStatusLabel(_selectedStatus),
                          style: const TextStyle(fontSize: 12),
                        ),
                        const SizedBox(width: 4),
                        GestureDetector(
                          onTap: () {
                            _statusTabController.animateTo(0);
                            context.read<OrderProvider>().filterByStatus('all');
                            setState(() => _selectedStatus = 'all');
                          },
                          child: const Icon(Icons.close, size: 12, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

          // Orders List
          Expanded(
            child: _buildOrdersList(orderProvider, orders),
          ),
        ],
      ),
    );
  }

  Tab _buildStatusTab(String label, int count, IconData icon) {
    return Tab(
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16),
          const SizedBox(width: 4),
          Text(label),
          const SizedBox(width: 4),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
            decoration: BoxDecoration(
              color: _selectedStatus == _getStatusFromTab(label) ? const Color(0xFFD4AF37).withOpacity(0.1) : Colors.grey[200],
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              count.toString(),
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: _selectedStatus == _getStatusFromTab(label) ? const Color(0xFFD4AF37) : Colors.grey[600],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _getStatusFromTab(String label) {
    switch (label) {
      case 'Tous': return 'all';
      case 'Nouveau': return 'new';
      case 'Traitement': return 'processing';
      case 'Expédié': return 'shipped';
      case 'Livré': return 'delivered';
      case 'Annulé': return 'cancelled';
      default: return 'all';
    }
  }

  String _getStatusLabel(String status) {
    switch (status) {
      case 'new': return 'Nouveau';
      case 'processing': return 'En traitement';
      case 'shipped': return 'Expédié';
      case 'delivered': return 'Livré';
      case 'cancelled': return 'Annulé';
      default: return '';
    }
  }

  Widget _buildOrdersList(OrderProvider provider, List<Order> orders) {
    if (provider.isLoading && orders.isEmpty) {
      return const LoadingWidget(message: 'Chargement des commandes...');
    }

    // OrderProvider sets an error when the request fails, and this screen never
    // read it — so a 401, a dropped connection or a server fault fell straight
    // through to the empty state below and told the customer "vous n'avez aucune
    // commande". Saying someone's order history is empty is a far worse answer
    // than saying the load failed, especially where those orders are paid in cash
    // on delivery. "No orders yet" and "we could not load your orders" are two
    // different things and now look different.
    if (provider.error != null && orders.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.cloud_off, size: 64, color: Colors.grey),
              const SizedBox(height: 16),
              Text(
                'Impossible de charger vos commandes',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey[800],
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                provider.error!,
                style: TextStyle(color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: () => provider.loadOrders(refresh: true),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                ),
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    if (orders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.shopping_bag_outlined,
              size: 80,
              color: Colors.grey[400],
            ),
            const SizedBox(height: 16),
            const Text(
              'Aucune commande',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.grey,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _selectedStatus != 'all'
                  ? 'Aucune commande avec le statut "${_getStatusLabel(_selectedStatus)}"'
                  : provider.searchQuery != null && provider.searchQuery!.isNotEmpty
                      ? 'Aucune commande trouvée pour "${provider.searchQuery}"'
                      : 'Vos commandes apparaîtront ici',
              style: TextStyle(color: Colors.grey),
            ),
            if (_selectedStatus != 'all')
              TextButton(
                onPressed: () {
                  _statusTabController.animateTo(0);
                  provider.filterByStatus('all');
                  setState(() => _selectedStatus = 'all');
                },
                child: const Text('Voir toutes les commandes'),
              )
            else if (provider.searchQuery != null && provider.searchQuery!.isNotEmpty)
              TextButton(
                onPressed: () {
                  _searchController.clear();
                  _searchOrders();
                },
                child: const Text('Effacer la recherche'),
              )
            else
              const SizedBox(height: 24),
            if (_selectedStatus == 'all' && (provider.searchQuery == null || provider.searchQuery!.isEmpty))
              ElevatedButton(
                onPressed: () => context.push('/shops'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                ),
                child: const Text('Commencer vos achats'),
              ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => provider.refreshOrders(),
      color: const Color(0xFFD4AF37),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: orders.length + (provider.hasMorePages ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == orders.length) {
            return _buildLoadMoreIndicator(provider);
          }
          return _buildOrderCard(orders[index], provider);
        },
      ),
    );
  }

  Widget _buildLoadMoreIndicator(OrderProvider provider) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Center(
        child: provider.isLoading
            ? const CircularProgressIndicator(color: Color(0xFFD4AF37))
            : TextButton(
                onPressed: () => provider.loadMoreOrders(),
                child: const Text('Charger plus...'),
              ),
      ),
    );
  }

  Widget _buildOrderCard(Order order, OrderProvider provider) {
    final currency = order.currency;
    final isCancelled = order.status == 'cancelled';
    
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      elevation: 0,
      child: InkWell(
        onTap: () => context.push('/orders/${order.id}'),
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          order.orderNumber,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          DateFormat('dd/MM/yyyy').format(order.createdAt),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  Row(
                    children: [
                      // Status Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: order.statusColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          order.statusLabel.toUpperCase(),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: order.statusColor,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      // Payment Status Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: order.paymentStatusColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          order.paymentStatusLabel.toUpperCase(),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: order.paymentStatusColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Vendor Name
              if (order.vendorName != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    children: [
                      const Icon(Icons.store, size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(
                        order.vendorName!,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),

              // Items Summary
              if (order.items.isNotEmpty)
                Text(
                  '${order.items.length} article${order.items.length > 1 ? 's' : ''}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),

              const SizedBox(height: 12),

              // Price Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Total',
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                      Text(
                        CurrencyFormatter.format(order.grandTotal, currency),
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFFD4AF37),
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Text(
                        'Reste à payer',
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                      Text(
                        CurrencyFormatter.format(order.totalRemaining, currency),
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: order.totalRemaining > 0 && !isCancelled ? Colors.red : Colors.green,
                        ),
                      ),
                    ],
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Action Buttons
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  const SizedBox(width: 8),
                  OutlinedButton(
                    onPressed: () => context.push('/orders/${order.id}'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.grey[700],
                      side: BorderSide(color: Colors.grey[300]!),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                    ),
                    child: const Text('Détails'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}