// lib/screens/help/faq_screen.dart

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class FaqScreen extends StatefulWidget {
  const FaqScreen({super.key});

  @override
  State<FaqScreen> createState() => _FaqScreenState();
}

class _FaqScreenState extends State<FaqScreen> with SingleTickerProviderStateMixin {
  final List<bool> _expandedItems = List.generate(10, (index) => false);
  final ScrollController _scrollController = ScrollController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Centre d\'Aide',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Color(0xFF1F2937),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => context.pop(),
        ),
      ),
      body: CustomScrollView(
        controller: _scrollController,
        slivers: [
          // Hero Section
          SliverToBoxAdapter(
            child: _buildHeroSection(),
          ),

          // FAQ Categories
          SliverToBoxAdapter(
            child: _buildCategoryTabs(),
          ),

          // Commandes Section
          SliverToBoxAdapter(
            child: _buildSectionHeader(
              'Commandes',
              icon: Icons.shopping_bag_outlined,
              id: 'commandes',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 0,
              question: 'Comment passer une commande ?',
              answer: 'Pour passer une commande, parcourez nos produits, ajoutez-les à votre panier, puis suivez les instructions de paiement. Vous recevrez un email de confirmation une fois votre commande validée.',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 1,
              question: 'Puis-je modifier ma commande après validation ?',
              answer: 'Vous pouvez modifier votre commande dans les 2 heures suivant sa validation en contactant notre service client. Passé ce délai, la commande est en cours de traitement.',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 2,
              question: 'Comment suivre ma commande ?',
              answer: 'Une fois votre commande expédiée, vous recevrez un email avec un numéro de suivi. Vous pouvez également suivre votre commande depuis votre compte dans la section "Mes commandes".',
            ),
          ),

          // Livraison Section
          SliverToBoxAdapter(
            child: _buildSectionHeader(
              'Livraison',
              icon: Icons.local_shipping_outlined,
              id: 'livraison',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildDeliveryInfo(),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 3,
              question: 'Quels sont les modes de livraison disponibles ?',
              answer: 'Nous proposons la livraison à domicile par nos partenaires logistiques et le retrait en point relais dans les grandes villes.',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 4,
              question: 'Que faire si ma commande n\'arrive pas ?',
              answer: 'Contactez notre service client dans les 48h suivant la date de livraison prévue. Nous ouvrirons une enquête auprès du transporteur.',
            ),
          ),

          // Paiements Section
          SliverToBoxAdapter(
            child: _buildSectionHeader(
              'Paiements',
              icon: Icons.credit_card_outlined,
              id: 'paiements',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 5,
              question: 'Quels moyens de paiement acceptez-vous ?',
              answer: 'Nous acceptons les cartes bancaires (Visa, Mastercard), Orange Money, Wave, et PayPal. Tous les paiements sont sécurisés.',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 6,
              question: 'Mes informations bancaires sont-elles sécurisées ?',
              answer: 'Oui, toutes vos transactions sont chiffrées via SSL. Nous ne stockons jamais vos informations de carte bancaire.',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 7,
              question: 'Puis-je payer en plusieurs fois ?',
              answer: 'Pour le moment, nous ne proposons pas de paiement en plusieurs fois. Cette option sera disponible prochainement.',
            ),
          ),

          // Retours Section
          SliverToBoxAdapter(
            child: _buildSectionHeader(
              'Retours et Remboursements',
              icon: Icons.undo_outlined,
              id: 'retours',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildReturnInfo(),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 8,
              question: 'Comment retourner un produit ?',
              answer: 'Connectez-vous à votre compte, allez dans "Mes commandes" et sélectionnez le produit à retourner. Suivez ensuite les instructions pour générer votre bon de retour.',
            ),
          ),
          SliverToBoxAdapter(
            child: _buildFaqItem(
              index: 9,
              question: 'Les frais de retour sont-ils gratuits ?',
              answer: 'Les retours pour défaut de fabrication sont gratuits. Pour les autres motifs, les frais de retour sont à la charge du client.',
            ),
          ),

          // CTA Section
          SliverToBoxAdapter(
            child: _buildCtaSection(),
          ),

          const SliverToBoxAdapter(child: SizedBox(height: 20)),
        ],
      ),
    );
  }

  Widget _buildHeroSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFFD4AF37), Color(0xFFC9A12F)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFD4AF37).withValues(alpha: 0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          const Text(
            'Foire Aux Questions',
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Trouvez rapidement des réponses à vos questions',
            style: TextStyle(
              fontSize: 16,
              color: Colors.white70,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          Container(
            height: 4,
            width: 80,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryTabs() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      height: 100,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          _buildCategoryChip('Commandes', Icons.shopping_bag_outlined, 'commandes'),
          _buildCategoryChip('Livraison', Icons.local_shipping_outlined, 'livraison'),
          _buildCategoryChip('Paiements', Icons.credit_card_outlined, 'paiements'),
          _buildCategoryChip('Retours', Icons.undo_outlined, 'retours'),
        ],
      ),
    );
  }

  Widget _buildCategoryChip(String label, IconData icon, String id) {
    return GestureDetector(
      onTap: () {
        _scrollToSection(id);
      },
      child: Container(
        width: 100,
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey[300]!),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: const Color(0xFFD4AF37), size: 20),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w500),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, {required IconData icon, required String id}) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 24, 16, 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: const Color(0xFFD4AF37), size: 20),
          ),
          const SizedBox(width: 12),
          Text(
            title,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2937),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFaqItem({
    required int index,
    required String question,
    required String answer,
  }) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _expandedItems[index] ? const Color(0xFFD4AF37) : Colors.grey[300]!),
      ),
      child: Column(
        children: [
          InkWell(
            onTap: () {
              setState(() {
                _expandedItems[index] = !_expandedItems[index];
              });
            },
            borderRadius: BorderRadius.circular(12),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      question,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: _expandedItems[index] ? FontWeight.bold : FontWeight.w500,
                        color: _expandedItems[index] ? const Color(0xFFD4AF37) : Colors.black87,
                      ),
                    ),
                  ),
                  Icon(
                    _expandedItems[index] ? Icons.keyboard_arrow_up : Icons.keyboard_arrow_down,
                    color: _expandedItems[index] ? const Color(0xFFD4AF37) : Colors.grey,
                  ),
                ],
              ),
            ),
          ),
          if (_expandedItems[index])
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Text(
                answer,
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey[600],
                  height: 1.5,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDeliveryInfo() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _buildDeliveryStat(
                  icon: Icons.timer_outlined,
                  title: 'Délais',
                  value: '3-7 jours',
                  color: Colors.blue,
                ),
              ),
              Expanded(
                child: _buildDeliveryStat(
                  icon: Icons.public_outlined,
                  title: 'Zones',
                  value: 'Afrique Ouest',
                  color: Colors.green,
                ),
              ),
              Expanded(
                child: _buildDeliveryStat(
                  icon: Icons.euro_outlined,
                  title: 'Frais',
                  value: 'Gratuit 50k+',
                  color: const Color(0xFFD4AF37),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDeliveryStat({
    required IconData icon,
    required String title,
    required String value,
    required Color color,
  }) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: color, size: 18),
        ),
        const SizedBox(height: 4),
        Text(
          title,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey[600],
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }

  Widget _buildReturnInfo() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Row(
        children: [
          Expanded(
            child: _buildReturnPolicy(
              icon: Icons.check_circle_outline,
              title: 'Retour accepté',
              description: 'Produit non utilisé, emballage intact',
              color: Colors.green,
            ),
          ),
          Container(
            height: 40,
            width: 1,
            color: Colors.grey[300],
          ),
          Expanded(
            child: _buildReturnPolicy(
              icon: Icons.cancel_outlined,
              title: 'Retour refusé',
              description: 'Produits personnalisés, hygiène',
              color: Colors.red,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReturnPolicy({
    required IconData icon,
    required String title,
    required String description,
    required Color color,
  }) {
    return Column(
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: 4),
        Text(
          title,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          description,
          style: TextStyle(
            fontSize: 10,
            color: Colors.grey[600],
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  // Update _buildCtaSection in faq_screen.dart:

Widget _buildCtaSection() {
  return Container(
    margin: const EdgeInsets.all(16),
    padding: const EdgeInsets.all(24),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [Color(0xFF1F2937), Color(0xFF111827)],
      ),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Column(
      children: [
        const Text(
          'Vous n\'avez pas trouvé votre réponse ?',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        const Text(
          'Notre équipe est là pour vous aider !',
          style: TextStyle(
            fontSize: 14,
            color: Colors.white70,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 16),
        
        // Contact Form Button - Nouveau bouton pour le formulaire
        Container(
          margin: const EdgeInsets.only(bottom: 12),
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: () => context.push('/contact'),
            icon: const Icon(Icons.chat_outlined),
            label: const Text('Formulaire de contact'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: const Color(0xFFD4AF37),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
        
        /* Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () {
                  context.push('/contact');
                },
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white30),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.email_outlined, size: 16),
                    SizedBox(width: 8),
                    Text('Email'),
                  ],
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton(
                onPressed: () async {
                  final Uri telUri = Uri(scheme: 'tel', path: '+221781234567');
                  if (await canLaunchUrl(telUri)) {
                    await launchUrl(telUri);
                  }
                },
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white30),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.phone_outlined, size: 16),
                    SizedBox(width: 8),
                    Text('Appeler'),
                  ],
                ),
              ),
            ),
          ],
        ),
       */
      ],
    ),
  );
}
  void _scrollToSection(String id) {
    double offset = 0;
    switch (id) {
      case 'commandes':
        offset = 400;
        break;
      case 'livraison':
        offset = 900;
        break;
      case 'paiements':
        offset = 1400;
        break;
      case 'retours':
        offset = 1900;
        break;
    }
    _scrollController.animateTo(
      offset,
      duration: const Duration(milliseconds: 500),
      curve: Curves.easeInOut,
    );
  }
}