// lib/screens/legal/privacy_screen.dart

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

class PrivacyScreen extends StatefulWidget {
  const PrivacyScreen({super.key});

  @override
  State<PrivacyScreen> createState() => _PrivacyScreenState();
}

class _PrivacyScreenState extends State<PrivacyScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Politique de Confidentialité',
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

          // Introduction Card
          SliverToBoxAdapter(
            child: _buildIntroductionCard(),
          ),

          // Table of Contents
          SliverToBoxAdapter(
            child: _buildTableOfContents(),
          ),

          // Section 1: Collecte
          SliverToBoxAdapter(
            child: _buildCollectionSection(),
          ),

          // Section 2: Utilisation
          SliverToBoxAdapter(
            child: _buildUsageSection(),
          ),

          // Section 3: Partage
          SliverToBoxAdapter(
            child: _buildSharingSection(),
          ),

          // Section 4: Sécurité
          SliverToBoxAdapter(
            child: _buildSecuritySection(),
          ),

          // Section 5: Cookies
          SliverToBoxAdapter(
            child: _buildCookiesSection(),
          ),

          // Section 6: Vos droits
          SliverToBoxAdapter(
            child: _buildRightsSection(),
          ),

          // Section 7: Contact
          SliverToBoxAdapter(
            child: _buildContactSection(),
          ),

          // Footer Note
          SliverToBoxAdapter(
            child: _buildFooter(),
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
      child: const Column(
        children: [
          Text(
            'Politique de',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w500,
              color: Colors.white70,
            ),
          ),
          Text(
            'Confidentialité',
            style: TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          SizedBox(height: 8),
          Text(
            'Protection de vos données personnelles',
            style: TextStyle(
              fontSize: 14,
              color: Colors.white70,
            ),
            textAlign: TextAlign.center,
          ),
          SizedBox(height: 16),
          Icon(
            Icons.privacy_tip_outlined,
            color: Colors.white,
            size: 48,
          ),
        ],
      ),
    );
  }

  Widget _buildIntroductionCard() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Text(
              'Protection des données',
              style: TextStyle(
                color: Color(0xFFD4AF37),
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(height: 16),
          RichText(
            textAlign: TextAlign.center,
            text: const TextSpan(
              style: TextStyle(fontSize: 14, color: Color(0xFF4B5563), height: 1.5),
              children: [
                TextSpan(text: 'Chez '),
                TextSpan(
                  text: 'MARA BUSINESS',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Color(0xFFD4AF37),
                  ),
                ),
                TextSpan(
                  text: ', nous accordons une importance capitale à la protection de vos données personnelles. Cette politique détaille comment nous collectons, utilisons et protégeons vos informations.'
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.update, size: 14, color: Colors.grey[600]),
                const SizedBox(width: 4),
                Text(
                  'Version 2.0 - Mise à jour le ${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}',
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTableOfContents() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.only(bottom: 12),
            child: Text(
              'Sommaire',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F2937),
              ),
            ),
          ),
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 4,
            childAspectRatio: 0.9,
            crossAxisSpacing: 8,
            mainAxisSpacing: 8,
            children: [
              _buildTocItem(number: '1', label: 'Collecte', section: 1),
              _buildTocItem(number: '2', label: 'Utilisation', section: 2),
              _buildTocItem(number: '3', label: 'Partage', section: 3),
              _buildTocItem(number: '4', label: 'Sécurité', section: 4),
              _buildTocItem(number: '5', label: 'Cookies', section: 5),
              _buildTocItem(number: '6', label: 'Vos droits', section: 6),
              _buildTocItem(number: '7', label: 'Contact', section: 7),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTocItem({
    required String number,
    required String label,
    required int section,
  }) {
    return GestureDetector(
      onTap: () => _scrollToSection(section),
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey[200]!),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              number,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFFD4AF37),
              ),
            ),
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCollectionSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionHeader(number: '1', title: 'Collecte des informations'),
          const SizedBox(height: 16),
          Text(
            'Nous collectons plusieurs types d\'informations dans le but de vous fournir et d\'améliorer nos services :',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[700],
            ),
          ),
          const SizedBox(height: 16),
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            childAspectRatio: 1.2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildInfoCard(
                icon: Icons.person_outline,
                title: 'Informations personnelles',
                description: 'Nom, prénom, email, téléphone, adresse',
              ),
              _buildInfoCard(
                icon: Icons.credit_card_outlined,
                title: 'Informations de paiement',
                description: 'Données de carte bancaire (chiffrées)',
              ),
              _buildInfoCard(
                icon: Icons.laptop_mac_outlined,
                title: 'Données de navigation',
                description: 'Adresse IP, type de navigateur, pages visitées',
              ),
              _buildInfoCard(
                icon: Icons.chat_bubble_outline,
                title: 'Communications',
                description: 'Correspondance avec notre service client',
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.blue[50],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, color: Colors.blue[700], size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Nous ne collectons jamais d\'informations sensibles sans votre consentement explicite.',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.blue[800],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCard({
    required IconData icon,
    required String title,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: const Color(0xFFD4AF37), size: 16),
          ),
          const SizedBox(height: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            description,
            style: TextStyle(
              fontSize: 11,
              color: Colors.grey[600],
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildUsageSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionHeader(number: '2', title: 'Utilisation de vos données'),
          const SizedBox(height: 16),
          ..._buildUsageItem(
            icon: Icons.shopping_bag_outlined,
            title: 'Traitement des commandes',
            description: 'Pour gérer vos achats, livraisons et services après-vente',
          ),
          ..._buildUsageItem(
            icon: Icons.send_outlined,
            title: 'Communication',
            description: 'Pour vous envoyer des mises à jour sur vos commandes',
          ),
          ..._buildUsageItem(
            icon: Icons.trending_up_outlined,
            title: 'Amélioration du service',
            description: 'Pour analyser et optimiser votre expérience',
          ),
          ..._buildUsageItem(
            icon: Icons.person_outline,
            title: 'Personnalisation',
            description: 'Pour vous proposer des offres adaptées',
          ),
        ],
      ),
    );
  }

  List<Widget> _buildUsageItem({
    required IconData icon,
    required String title,
    required String description,
  }) {
    return [
      Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: Colors.green[50],
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.check, color: Colors.green[600], size: 14),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  description,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      const SizedBox(height: 12),
    ];
  }

  Widget _buildSharingSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionHeader(number: '3', title: 'Partage des informations'),
          const SizedBox(height: 16),
          Text(
            'Nous ne vendons jamais vos données personnelles. Elles peuvent être partagées uniquement dans les cas suivants :',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[700],
            ),
          ),
          const SizedBox(height: 16),
          _buildSharingItem(
            icon: Icons.local_shipping_outlined,
            text: 'Partenaires logistiques : Pour assurer la livraison',
          ),
          _buildSharingItem(
            icon: Icons.credit_card_outlined,
            text: 'Prestataires de paiement : Pour traiter vos transactions',
          ),
          _buildSharingItem(
            icon: Icons.gavel_outlined,
            text: 'Obligations légales : Si la loi nous y oblige',
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.yellow[50],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                Icon(Icons.warning_amber_rounded, color: Colors.yellow[700], size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Tous nos partenaires sont tenus de respecter la confidentialité de vos données.',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.yellow[800],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSharingItem({
    required IconData icon,
    required String text,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: const Color(0xFFD4AF37), size: 16),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSecuritySection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionHeader(number: '4', title: 'Sécurité des données'),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildSecurityCard(
                  icon: Icons.lock_outline,
                  title: 'Chiffrement SSL',
                  description: 'Connexion sécurisée',
                  color: Colors.green,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildSecurityCard(
                  icon: Icons.dns_outlined,
                  title: 'Serveurs sécurisés',
                  description: 'Certifiés ISO 27001',
                  color: Colors.blue,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildSecurityCard(
                  icon: Icons.admin_panel_settings_outlined,
                  title: 'Accès restreint',
                  description: 'Employés autorisés uniquement',
                  color: Colors.purple,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSecurityCard({
    required IconData icon,
    required String title,
    required String description,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
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
            textAlign: TextAlign.center,
          ),
          Text(
            description,
            style: TextStyle(
              fontSize: 10,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildCookiesSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionHeader(number: '5', title: 'Cookies et technologies'),
          const SizedBox(height: 16),
          Text(
            'Nous utilisons des cookies pour améliorer votre expérience :',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[700],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey[200]!),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Table(
              columnWidths: const {
                0: FlexColumnWidth(1),
                1: FlexColumnWidth(2),
                2: FlexColumnWidth(1),
              },
              children: [
                _buildTableRow(
                  type: 'Essentiels',
                  purpose: 'Fonctionnement du site',
                  duration: 'Session',
                  isHeader: true,
                ),
                _buildTableRow(
                  type: 'Fonctionnels',
                  purpose: 'Préférences',
                  duration: '1 an',
                ),
                _buildTableRow(
                  type: 'Analytiques',
                  purpose: 'Audience',
                  duration: '13 mois',
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              TextButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.settings_outlined, size: 16),
                label: const Text('Gérer mes préférences'),
                style: TextButton.styleFrom(
                  foregroundColor: const Color(0xFFD4AF37),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  TableRow _buildTableRow({
    required String type,
    required String purpose,
    required String duration,
    bool isHeader = false,
  }) {
    return TableRow(
      decoration: BoxDecoration(
        color: isHeader ? Colors.grey[50] : Colors.transparent,
      ),
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(
            type,
            style: TextStyle(
              fontSize: 12,
              fontWeight: isHeader ? FontWeight.bold : FontWeight.normal,
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(
            purpose,
            style: TextStyle(
              fontSize: 12,
              color: isHeader ? Colors.black87 : Colors.grey[700],
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(
            duration,
            style: TextStyle(
              fontSize: 12,
              color: isHeader ? Colors.black87 : Colors.grey[700],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildRightsSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSectionHeader(number: '6', title: 'Vos droits (RGPD)'),
          const SizedBox(height: 16),
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            childAspectRatio: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            children: [
              _buildRightCard(
                icon: Icons.visibility_outlined,
                title: "Droit d'accès",
                description: 'Consulter vos données',
              ),
              _buildRightCard(
                icon: Icons.edit_outlined,
                title: 'Droit de rectification',
                description: 'Modifier vos informations',
              ),
              _buildRightCard(
                icon: Icons.delete_outline,
                title: "Droit à l'effacement",
                description: 'Supprimer vos données',
              ),
              _buildRightCard(
                icon: Icons.block_outlined,
                title: "Droit d'opposition",
                description: 'Vous opposer au traitement',
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey[50],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                Icon(Icons.email_outlined, color: Colors.grey[600], size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: RichText(
                    text: TextSpan(
                      style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                      children: [
                        const TextSpan(text: 'Pour exercer vos droits : '),
                        TextSpan(
                          text: 'dpo@marabusiness.com',
                          style: const TextStyle(
                            color: Color(0xFFD4AF37),
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRightCard({
    required IconData icon,
    required String title,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: const Color(0xFFD4AF37), size: 14),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  description,
                  style: TextStyle(
                    fontSize: 9,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // In _buildContactSection, update the "Nous contacter" section to link to the contact form:

Widget _buildContactSection() {
  return Container(
    margin: const EdgeInsets.all(16),
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      boxShadow: [
        BoxShadow(
          color: Colors.grey.withValues(alpha: 0.1),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ],
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader(number: '7', title: 'Nous contacter'),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _buildContactCard(
                icon: Icons.email_outlined,
                label: 'Email',
                value: 'privacy@marabusiness.com',
                onTap: () async {
                  final Uri emailUri = Uri(
                    scheme: 'mailto',
                    path: 'privacy@marabusiness.com',
                  );
                  if (await canLaunchUrl(emailUri)) {
                    await launchUrl(emailUri);
                  }
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildContactCard(
                icon: Icons.phone_outlined,
                label: 'Téléphone',
                value: '+221 78 123 45 67',
                onTap: () async {
                  final Uri telUri = Uri(scheme: 'tel', path: '+221781234567');
                  if (await canLaunchUrl(telUri)) {
                    await launchUrl(telUri);
                  }
                },
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        
        // ADD THIS - Contact Form Button
        GestureDetector(
          onTap: () => context.push('/contact'),
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFFD4AF37), Color(0xFFC9A12F)],
              ),
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFD4AF37).withValues(alpha: 0.3),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.chat_outlined,
                    color: Colors.white,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text(
                        'Une question ?',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Contactez notre équipe via le formulaire',
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.arrow_forward_ios,
                  color: Colors.white,
                  size: 16,
                ),
              ],
            ),
          ),
        ),
        
        const SizedBox(height: 12),
        
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.admin_panel_settings_outlined,
                  color: Color(0xFFD4AF37),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Délégué à la Protection',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'M. Amadou Diallo',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                    GestureDetector(
                      onTap: () async {
                        final Uri emailUri = Uri(
                          scheme: 'mailto',
                          path: 'dpo@marabusiness.com',
                        );
                        if (await canLaunchUrl(emailUri)) {
                          await launchUrl(emailUri);
                        }
                      },
                      child: const Text(
                        'dpo@marabusiness.com',
                        style: TextStyle(
                          fontSize: 12,
                          color: Color(0xFFD4AF37),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

  Widget _buildContactCard({
    required IconData icon,
    required String label,
    required String value,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.grey[50],
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: const Color(0xFFD4AF37), size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                color: Colors.grey[600],
              ),
            ),
            Text(
              value,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFooter() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.transparent,
      ),
      child: Column(
        children: [
          const Divider(),
          const SizedBox(height: 8),
          Text(
            'Cette politique peut être mise à jour périodiquement. Dernière mise à jour : ${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}',
            style: TextStyle(
              fontSize: 11,
              color: Colors.grey[500],
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader({
    required String number,
    required String title,
  }) {
    return Row(
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Center(
            child: Text(
              number,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Color(0xFFD4AF37),
              ),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2937),
            ),
          ),
        ),
      ],
    );
  }

  void _scrollToSection(int section) {
    double offset = 0;
    switch (section) {
      case 1:
        offset = 600;
        break;
      case 2:
        offset = 1300;
        break;
      case 3:
        offset = 1800;
        break;
      case 4:
        offset = 2300;
        break;
      case 5:
        offset = 2800;
        break;
      case 6:
        offset = 3400;
        break;
      case 7:
        offset = 4000;
        break;
    }
    _scrollController.animateTo(
      offset,
      duration: const Duration(milliseconds: 500),
      curve: Curves.easeInOut,
    );
  }
}