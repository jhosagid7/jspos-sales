import 'package:flutter_test/flutter_test.dart';

double calculateUsdEquivalent(double amountLocal, double rate, bool isUSD) {
  if (isUSD) return amountLocal;
  if (rate <= 0) return 0.0;
  return amountLocal / rate;
}

double calculateInitialLocalAmount(double debtUSD, double rate, bool isUSD) {
  if (isUSD || rate <= 0) return debtUSD;
  return debtUSD * rate;
}

double handleDateRateChange({
  required double debtUSD,
  required double newRate,
  required bool isUSD,
  required bool userHasEdited,
  required double currentAmount,
}) {
  if (!userHasEdited) {
    return calculateInitialLocalAmount(debtUSD, newRate, isUSD);
  }
  return currentAmount;
}

void main() {
  group('Payment Conversion Multi-Currency Tests', () {
    test('VED currency converts debt and calculates USD equivalent correctly', () {
      double rateVED = 870.0;
      double initialBs = calculateInitialLocalAmount(252.28, rateVED, false);
      expect(initialBs, closeTo(219483.60, 0.01));
      expect(calculateUsdEquivalent(initialBs, rateVED, false), closeTo(252.28, 0.01));
      expect(calculateUsdEquivalent(100000.0, rateVED, false), closeTo(114.94, 0.01));
    });

    test('COP currency converts debt to Pesos and calculates USD equivalent correctly', () {
      double rateCOP = 4000.0; // 1 USD = 4000 COP
      double initialCOP = calculateInitialLocalAmount(252.28, rateCOP, false);
      expect(initialCOP, closeTo(1009120.0, 0.1));
      expect(calculateUsdEquivalent(initialCOP, rateCOP, false), closeTo(252.28, 0.01));
      expect(calculateUsdEquivalent(500000.0, rateCOP, false), closeTo(125.0, 0.01));
    });

    test('USD currency keeps amount as USD debt without scaling', () {
      double initialUSD = calculateInitialLocalAmount(252.28, 1.0, true);
      expect(initialUSD, 252.28);
      expect(calculateUsdEquivalent(initialUSD, 1.0, true), 252.28);
    });

    test('Date rate change updates unedited local amount but respects user edited amount', () {
      double debtUSD = 788.18;
      double oldRate = 870.0;
      double newRate = 890.0;

      double uneditedAmount = calculateInitialLocalAmount(debtUSD, oldRate, false);
      double updatedUnedited = handleDateRateChange(
        debtUSD: debtUSD,
        newRate: newRate,
        isUSD: false,
        userHasEdited: false,
        currentAmount: uneditedAmount,
      );
      expect(updatedUnedited, closeTo(701480.20, 0.01));
      expect(calculateUsdEquivalent(updatedUnedited, newRate, false), closeTo(788.18, 0.01));

      double userEditedAmount = 350000.0;
      double updatedUserEdited = handleDateRateChange(
        debtUSD: debtUSD,
        newRate: newRate,
        isUSD: false,
        userHasEdited: true,
        currentAmount: userEditedAmount,
      );
      expect(updatedUserEdited, 350000.0);
      expect(calculateUsdEquivalent(updatedUserEdited, newRate, false), closeTo(393.26, 0.01));
    });
  });
}
