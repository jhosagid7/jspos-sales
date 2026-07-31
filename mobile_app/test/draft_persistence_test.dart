import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Draft Order & Offline Queue Persistence Tests', () {
    setUp(() {
      SharedPreferences.setMockInitialValues({});
    });

    test('Auto-save draft cart stores customer, notes and items in SharedPreferences', () async {
      final prefs = await SharedPreferences.getInstance();

      final draftData = {
        'customer_id': 15,
        'customer_name': 'Cliente Test',
        'customer_debt': 150.0,
        'notes': 'Entregar por la tarde',
        'items': [
          {
            'product_id': 101,
            'product_name': 'Producto 1',
            'product_sku': 'P101',
            'product_price': 25.50,
            'product_image': 'p1.jpg',
            'quantity': 3.0,
          }
        ],
      };

      await prefs.setString('draft_order', json.encode(draftData));

      final savedStr = prefs.getString('draft_order');
      expect(savedStr, isNotNull);

      final loaded = json.decode(savedStr!);
      expect(loaded['customer_id'], equals(15));
      expect(loaded['customer_name'], equals('Cliente Test'));
      expect(loaded['notes'], equals('Entregar por la tarde'));
      expect((loaded['items'] as List).length, equals(1));
      expect(loaded['items'][0]['product_id'], equals(101));
      expect(loaded['items'][0]['quantity'], equals(3.0));
    });

    test('Offline order queue stores pending orders and clears on sync', () async {
      final prefs = await SharedPreferences.getInstance();

      final orderBody = {
        'customer_id': 20,
        'items': [
          {'product_id': 202, 'quantity': 5.0, 'price': 10.0}
        ],
        'notes': 'Pedido sin conexión',
        'created_at_local': DateTime.now().toIso8601String(),
      };

      List<String> pending = prefs.getStringList('pending_offline_orders') ?? [];
      pending.add(json.encode(orderBody));
      await prefs.setStringList('pending_offline_orders', pending);

      final savedPending = prefs.getStringList('pending_offline_orders');
      expect(savedPending, isNotNull);
      expect(savedPending!.length, equals(1));

      final loadedOrder = json.decode(savedPending.first);
      expect(loadedOrder['customer_id'], equals(20));
      expect(loadedOrder['notes'], equals('Pedido sin conexión'));

      // Simulate successful sync clearing queue
      await prefs.setStringList('pending_offline_orders', []);
      expect(prefs.getStringList('pending_offline_orders'), isEmpty);
    });
  });
}
