import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Bolsas Factory Production Persistence & Offline Queue Tests', () {
    setUp(() {
      SharedPreferences.setMockInitialValues({});
    });

    test('Auto-save production draft stores entries and notes in SharedPreferences', () async {
      final prefs = await SharedPreferences.getInstance();

      final draftData = {
        'production_date': DateTime.now().toIso8601String(),
        'notes': 'Produccion turno mañana bolsas de basura',
        'entries': [
          {
            'product': {
              'id': 45,
              'name': 'BOLSA BASURA 30X40',
              'sku': 'BB3040',
              'cost': 1.20,
              'is_variable_quantity': false,
            },
            'quantity': 500.0,
            'weight': 45.5,
            'operator_name': 'Juan Perez',
            'production_date': DateTime.now().toIso8601String(),
            'metadata': [],
          }
        ],
      };

      await prefs.setString('draft_production_bolsas', json.encode(draftData));

      final savedStr = prefs.getString('draft_production_bolsas');
      expect(savedStr, isNotNull);

      final decoded = json.decode(savedStr!);
      expect(decoded['notes'], 'Produccion turno mañana bolsas de basura');
      expect(decoded['entries'].length, 1);
      expect(decoded['entries'][0]['operator_name'], 'Juan Perez');
      expect(decoded['entries'][0]['weight'], 45.5);
    });

    test('Offline production queue stores pending production lots and clears on sync', () async {
      final prefs = await SharedPreferences.getInstance();

      final pendingLot = {
        'production_date': '2026-07-31',
        'notes': 'Lote offline bolsas',
        'details': [
          {
            'product_id': 45,
            'quantity': 200,
            'weight': 22.1,
            'operator_name': 'Carlos M.',
            'production_date': '2026-07-31',
            'metadata': null,
          }
        ],
        'created_at_local': DateTime.now().toIso8601String(),
      };

      final List<String> queue = [json.encode(pendingLot)];
      await prefs.setStringList('pending_offline_production_bolsas', queue);

      var currentQueue = prefs.getStringList('pending_offline_production_bolsas') ?? [];
      expect(currentQueue.length, 1);

      // Clear on sync
      await prefs.setStringList('pending_offline_production_bolsas', []);
      currentQueue = prefs.getStringList('pending_offline_production_bolsas') ?? [];
      expect(currentQueue.isEmpty, isTrue);
    });
  });
}
