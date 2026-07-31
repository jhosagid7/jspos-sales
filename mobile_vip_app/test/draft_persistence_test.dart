import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('VIP Draft Order & Offline Queue Persistence Tests', () {
    setUp(() {
      SharedPreferences.setMockInitialValues({});
    });

    test('Auto-save draft cart stores notes and items in SharedPreferences for VIP', () async {
      final prefs = await SharedPreferences.getInstance();

      final draftData = {
        'customer_id': 105,
        'customer_name': 'Cliente VIP Test',
        'notes': 'Entregar antes del mediodia',
        'items': [
          {
            'product_id': 12,
            'product_name': 'BANDEJAS A1 EUROPLAST',
            'product_sku': 'BP03A1E',
            'product_price': 12.94,
            'quantity': 3.0,
            'customer_id': 105,
          }
        ],
      };

      await prefs.setString('draft_order_vip', json.encode(draftData));

      final savedStr = prefs.getString('draft_order_vip');
      expect(savedStr, isNotNull);

      final decoded = json.decode(savedStr!);
      expect(decoded['customer_id'], 105);
      expect(decoded['notes'], 'Entregar antes del mediodia');
      expect(decoded['items'].length, 1);
      expect(decoded['items'][0]['quantity'], 3.0);
    });

    test('VIP Offline order queue stores pending orders and clears on sync', () async {
      final prefs = await SharedPreferences.getInstance();

      final pendingOrder = {
        'customer_id': 105,
        'items': [
          {'product_id': 12, 'quantity': 5, 'price': 12.94}
        ],
        'notes': 'Pedido offline VIP',
        'created_at_local': DateTime.now().toIso8601String(),
      };

      final List<String> queue = [json.encode(pendingOrder)];
      await prefs.setStringList('pending_offline_orders_vip', queue);

      var currentQueue = prefs.getStringList('pending_offline_orders_vip') ?? [];
      expect(currentQueue.length, 1);

      // Simulate successful sync clearing the queue
      await prefs.setStringList('pending_offline_orders_vip', []);
      currentQueue = prefs.getStringList('pending_offline_orders_vip') ?? [];
      expect(currentQueue.isEmpty, isTrue);
    });
  });
}
