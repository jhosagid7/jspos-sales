import 'package:flutter_test/flutter_test.dart';
import 'package:jspos_mobile/main.dart';

void main() {
  testWidgets('App renders login screen smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const JSPOSMobile());
    expect(find.text('JSPOS VIP'), findsOneWidget);
  });
}
