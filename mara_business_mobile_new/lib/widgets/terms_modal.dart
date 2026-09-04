import 'dart:io';
import 'package:flutter/services.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class TermsModal extends StatelessWidget {
  final bool acceptedTerms;
  final Function(bool?) onTermsChanged;
  final VoidCallback onAccept;
  final VoidCallback onCancel;
  final String? pdfUrl; // New optional parameter

  const TermsModal({
    super.key,
    required this.acceptedTerms,
    required this.onTermsChanged,
    required this.onAccept,
    required this.onCancel,
    this.pdfUrl,
  });

  Future<void> _launchPDF() async {
    final pdfAssetPath = pdfUrl; // e.g., 'assets/CommissionContract.pdf'
    if (pdfAssetPath == null) return;

    try {
      // 1. Load the PDF from assets as byte data
      final ByteData byteData = await rootBundle.load(pdfAssetPath);
      final List<int> pdfBytes = byteData.buffer.asUint8List();

      // 2. Get a temporary directory to write the file
      final Directory tempDir = await getTemporaryDirectory();
      final File tempFile = File('${tempDir.path}/commission_contract.pdf');

      // 3. Write the PDF bytes to the temporary file
      await tempFile.writeAsBytes(pdfBytes);

      // 4. Open the temporary file with the system PDF viewer
      final OpenResult result = await OpenFilex.open(tempFile.path);

      if (result.type != ResultType.done) {
        throw Exception('Could not open PDF: ${result.message}');
      }
    } catch (e) {
      debugPrint('Error opening PDF: $e');
      // Optional: Show a user-friendly error message
      // ScaffoldMessenger.of(context).showSnackBar(...);
      rethrow;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: double.maxFinite,
        constraints: const BoxConstraints(maxWidth: 500, maxHeight: 600),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // HEADER (unchanged)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
              ),
              child: Row(
                children: [
                  Flexible(
                    child: Text(
                      'Conditions & Contrat de Commission',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    icon: const Icon(Icons.close, size: 20),
                    onPressed: onCancel,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                    splashRadius: 20,
                  ),
                ],
              ),
            ),

            // CONTENT
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Marketplace Rules
                    _buildSection(
                      icon: Icons.store,
                      title: 'Règles de la Marketplace',
                      children: _buildRulesList([
                        'Vous devez fournir des informations de boutique précises.',
                        'Les produits doivent être authentiques et légaux à la vente.',
                        'Les commandes doivent être traitées dans les délais.',
                        'Les violations répétées peuvent entraîner une suspension.',
                      ]),
                    ),
                    const SizedBox(height: 16),

                    // Commission Contract
                    _buildSection(
                      icon: Icons.receipt,
                      title: 'Contrat de Commission',
                      children: [
                        const Text(
                          'En rejoignant la plateforme, vous acceptez qu\'une commission '
                          'par commande puisse être prélevée (pourcentage ou frais fixes '
                          'selon votre formule et votre mode de paiement).',
                          style: TextStyle(fontSize: 14, height: 1.5),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // PDF Link (NEW)
                    if (pdfUrl != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: InkWell(
                          onTap: _launchPDF,
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                            decoration: BoxDecoration(
                              color: Colors.grey[100],
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.grey[300]!),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.picture_as_pdf, color: Colors.red, size: 24),
                                const SizedBox(width: 12),
                                const Expanded(
                                  child: Text(
                                    'Télécharger le contrat de commission (PDF)',
                                    style: TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.w500,
                                      decoration: TextDecoration.underline,
                                    ),
                                  ),
                                ),
                                const Icon(Icons.open_in_new, size: 16, color: Colors.grey),
                              ],
                            ),
                          ),
                        ),
                      ),

                    // Accept Terms (unchanged except position – it's now below PDF link)
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.blue[50],
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.blue[200]!),
                      ),
                      child: Row(
                        children: [
                          SizedBox(
                            height: 24,
                            width: 24,
                            child: Checkbox(
                              value: acceptedTerms,
                              onChanged: onTermsChanged,
                              activeColor: const Color(0xFFD4AF37),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          const Flexible(
                            child: Text(
                              'J\'ai lu et j\'accepte les conditions et le contrat de commission.',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // FOOTER (unchanged)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: Colors.grey[200]!)),
              ),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    TextButton(
                      onPressed: onCancel,
                      child: const Text('Annuler'),
                    ),
                    const SizedBox(width: 12),
                    ElevatedButton(
                      onPressed: onAccept,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFD4AF37),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      child: const Text('J\'accepte - Continuer'),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({
    required IconData icon,
    required String title,
    required List<Widget> children,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFD4AF37).withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: const Color(0xFFD4AF37), size: 16),
              ),
              const SizedBox(width: 8),
              Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  List<Widget> _buildRulesList(List<String> rules) {
    return rules.map((rule) => Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('• ', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
          Flexible(
            child: Text(
              rule,
              style: const TextStyle(fontSize: 14, height: 1.5),
            ),
          ),
        ],
      ),
    )).toList();
  }
}