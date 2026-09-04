import 'package:flutter/material.dart';
import 'services/api_service.dart';
import 'core/constants/app_constants.dart';

class TestApiScreen extends StatefulWidget {
  const TestApiScreen({super.key});

  @override
  State<TestApiScreen> createState() => _TestApiScreenState();
}

class _TestApiScreenState extends State<TestApiScreen> {
  String _status = 'Testing...';
  final apiService = ApiService(baseUrl: AppConstants.baseUrl);

  @override
  void initState() {
    super.initState();
    testConnection();
  }

  Future<void> testConnection() async {
    try {
      final response = await apiService.getHomeData();
      setState(() {
        _status = response.success 
            ? '✅ Connected! Data received'
            : '❌ Error: ${response.message}';
      });
    } catch (e) {
      setState(() {
        _status = '❌ Failed: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('API Test')),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(_status),
              const SizedBox(height: 20),
              const Text('Base URL: ${AppConstants.baseUrl}'),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () {
                  setState(() {
                    _status = 'Testing...';
                  });
                  testConnection();
                },
                child: const Text('Test Again'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}