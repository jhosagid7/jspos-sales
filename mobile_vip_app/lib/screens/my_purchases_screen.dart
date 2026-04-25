import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:intl/intl.dart';

class MyPurchasesScreen extends StatefulWidget {
  final String baseUrl;
  const MyPurchasesScreen({Key? key, required this.baseUrl}) : super(key: key);

  @override
  _MyPurchasesScreenState createState() => _MyPurchasesScreenState();
}

class _MyPurchasesScreenState extends State<MyPurchasesScreen> {
  bool _isLoading = true;
  List<dynamic> _sales = [];
  final formatCurrency = NumberFormat.currency(locale: "en_US", symbol: "\$");

  @override
  void initState() { super.initState(); _fetchSales(); }

  Future<void> _fetchSales() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';
    try {
      final response = await http.get(
        Uri.parse('${widget.baseUrl}/api/vip/sales'),
        headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'},
      );
      if (response.statusCode == 200) {
        setState(() { _sales = json.decode(response.body); _isLoading = false; });
      } else {
        setState(() { _isLoading = false; });
      }
    } catch (e) {
      setState(() { _isLoading = false; });
    }
  }

  void _showDetails(dynamic sale) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        height: MediaQuery.of(context).size.height * 0.7,
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(sale['invoice_number'] ?? 'Factura', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView.builder(
                itemCount: (sale['details'] as List?)?.length ?? 0,
                itemBuilder: (context, index) {
                  final detail = sale['details'][index];
                  final product = detail['product'];
                  return ListTile(
                    title: Text(product != null ? product['name'] : 'Producto desconocido', style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('Cant: ${detail['quantity']}   •   Precio: ${formatCurrency.format(double.tryParse(detail['sale_price']?.toString() ?? '0'))}'),
                    trailing: Text(formatCurrency.format((double.tryParse(detail['quantity']?.toString() ?? '0') ?? 0) * (double.tryParse(detail['sale_price']?.toString() ?? '0') ?? 0)), style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF00B4D8))),
                  );
                },
              ),
            ),
            Container(
              padding: const EdgeInsets.all(20),
              color: Colors.grey.shade50,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('TOTAL:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text(formatCurrency.format(double.tryParse(sale['total']?.toString() ?? '0')), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20, color: Color(0xFF00B4D8))),
                ],
              ),
            )
          ],
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'paid': return Colors.green;
      case 'pending': return Colors.orange;
      default: return Colors.red;
    }
  }
  
  String _getStatusText(String status) {
    switch (status) {
      case 'paid': return 'PAGADA';
      case 'pending': return 'PENDIENTE';
      case 'cancelled': return 'CANCELADA';
      case 'voided': return 'ANULADA';
      case 'returned': return 'DEVUELTA';
      default: return status.toUpperCase();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade100,
      appBar: AppBar(title: const Text('Mis Compras', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)), backgroundColor: const Color(0xFF00B4D8), elevation: 0, iconTheme: const IconThemeData(color: Colors.white)),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF00B4D8)))
          : _sales.isEmpty
              ? Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.receipt_long, size: 80, color: Colors.grey.shade300), const SizedBox(height: 20), Text('No tienes compras registradas', style: TextStyle(color: Colors.grey.shade600))]))
              : ListView.builder(
                  padding: const EdgeInsets.all(15),
                  itemCount: _sales.length,
                  itemBuilder: (context, index) {
                    final sale = _sales[index];
                    return Card(
                      elevation: 2,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                      margin: const EdgeInsets.only(bottom: 15),
                      child: InkWell(
                        onTap: () => _showDetails(sale),
                        borderRadius: BorderRadius.circular(15),
                        child: Padding(
                          padding: const EdgeInsets.all(15),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(sale['invoice_number'] ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(color: _getStatusColor(sale['status'] ?? '').withOpacity(0.1), borderRadius: BorderRadius.circular(20)),
                                    child: Text(_getStatusText(sale['status'] ?? ''), style: TextStyle(color: _getStatusColor(sale['status'] ?? ''), fontSize: 10, fontWeight: FontWeight.bold)),
                                  )
                                ],
                              ),
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  Icon(Icons.calendar_today, size: 14, color: Colors.grey.shade500),
                                  const SizedBox(width: 5),
                                  Text(sale['created_at'] != null ? DateFormat('dd/MM/yyyy hh:mm a').format(DateTime.parse(sale['created_at']).toLocal()) : '', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text('${(sale['details'] as List?)?.length ?? 0} ítems', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                                  Text(formatCurrency.format(double.tryParse(sale['total']?.toString() ?? '0')), style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF00B4D8))),
                                ],
                              )
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}
