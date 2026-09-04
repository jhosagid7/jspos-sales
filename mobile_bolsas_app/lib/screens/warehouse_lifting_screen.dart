import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:mobile_scanner/mobile_scanner.dart';
import '../constants.dart';

class WarehouseLiftingScreen extends StatefulWidget {
  final String baseUrl;
  final String token;
  final String userName;

  const WarehouseLiftingScreen({
    super.key,
    required this.baseUrl,
    required this.token,
    required this.userName,
  });

  @override
  State<WarehouseLiftingScreen> createState() => _WarehouseLiftingScreenState();
}

class _WarehouseLiftingScreenState extends State<WarehouseLiftingScreen> {
  bool _isLoading = true;
  bool _isSubmitting = false;
  bool _isScannerOpen = false;

  List<dynamic> _pendingBultos = [];
  final Set<int> _selectedIds = {};

  final MobileScannerController _scannerController = MobileScannerController(
    detectionSpeed: DetectionSpeed.normal,
    facing: CameraFacing.back,
  );

  @override
  void initState() {
    super.initState();
    _fetchPendingLifting();
  }

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  String get _cleanUrl => widget.baseUrl.replaceAll(RegExp(r'/+$'), '');

  Map<String, String> get _authHeaders => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ${widget.token}',
      };

  Future<void> _fetchPendingLifting() async {
    setState(() => _isLoading = true);
    try {
      final res = await http
          .get(
            Uri.parse('$_cleanUrl/api/bag-factory/lifting/pending'),
            headers: _authHeaders,
          )
          .timeout(const Duration(seconds: 10));

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (mounted) {
          setState(() {
            _pendingBultos = data['data'] ?? [];
            // Auto select all by default for fast bulk receiving if desired
          });
        }
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error de conexión con el VPS'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _onQrScanned(String code) async {
    final cleanCode = code.trim();
    if (cleanCode.isEmpty) return;

    // Check if already in pending list
    final existingIndex = _pendingBultos.indexWhere((b) => b['qr_code'] == cleanCode);
    if (existingIndex != -1) {
      final item = _pendingBultos[existingIndex];
      final id = item['id'] as int;
      if (!_selectedIds.contains(id)) {
        setState(() {
          _selectedIds.add(id);
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('🎯 Bulto escaneado: ${item['product_name']} (${item['weight']} Kg)'),
            backgroundColor: const Color(0xFF10B981),
            duration: const Duration(seconds: 2),
          ),
        );
      }
      return;
    }

    // Else query VPS
    try {
      final res = await http.get(
        Uri.parse('$_cleanUrl/api/bag-factory/lifting/scan/$cleanCode'),
        headers: _authHeaders,
      );
      if (res.statusCode == 200) {
        final data = json.decode(res.body)['data'];
        if (data['is_ready'] == true) {
          final id = data['id'] as int;
          setState(() {
            if (!_pendingBultos.any((b) => b['id'] == id)) {
              _pendingBultos.insert(0, data);
            }
            _selectedIds.add(id);
          });
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('✅ Bulto listo agregado: ${data['product_name']}'),
                backgroundColor: const Color(0xFF10B981),
              ),
            );
          }
        } else {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('⚠️ El bulto $cleanCode no está en estado aprobado para levantamiento'),
                backgroundColor: Colors.orange,
              ),
            );
          }
        }
      }
    } catch (_) {}
  }

  Future<void> _submitLifting() async {
    if (_selectedIds.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Seleccione al menos un bulto para recibir en almacén')),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final res = await http
          .post(
            Uri.parse('$_cleanUrl/api/bag-factory/lifting/receive'),
            headers: _authHeaders,
            body: json.encode({
              'production_ids': _selectedIds.toList(),
              'notes': 'Recepción en Almacén por ${widget.userName}',
            }),
          )
          .timeout(const Duration(seconds: 12));

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (mounted) {
          _selectedIds.clear();
          await showDialog(
            context: context,
            builder: (ctx) => AlertDialog(
              backgroundColor: const Color(0xFF1E293B),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Row(
                children: [
                  const Icon(Icons.check_circle, color: Color(0xFF10B981), size: 28),
                  const SizedBox(width: 10),
                  Text('Recepción Exitosa',
                      style: GoogleFonts.plusJakartaSans(color: Colors.white, fontWeight: FontWeight.bold)),
                ],
              ),
              content: Text(
                '${data['message']}\n\nLos bultos ya forman parte del inventario oficial de JSPOS.',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 13),
              ),
              actions: [
                ElevatedButton(
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF10B981)),
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('ENTENDIDO', style: TextStyle(color: Colors.white)),
                ),
              ],
            ),
          );
          await _fetchPendingLifting();
        }
      } else {
        final data = json.decode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['message'] ?? 'Error al procesar levantamiento'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error de conexión: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  double get _selectedWeight {
    return _pendingBultos
        .where((b) => _selectedIds.contains(b['id']))
        .fold(0.0, (acc, b) => acc + ((b['weight'] ?? 0.0) as num).toDouble());
  }

  double get _selectedPkgs {
    return _pendingBultos
        .where((b) => _selectedIds.contains(b['id']))
        .fold(0.0, (acc, b) => acc + ((b['quantity'] ?? 0.0) as num).toDouble());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B132B),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1C2541),
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Recepción Almacén JSPOS v$kAppVersion',
                style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
            Text('Levantamiento Oficial • Personal: ${widget.userName}',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 11)),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(_isScannerOpen ? Icons.close : Icons.qr_code_scanner, color: const Color(0xFF38BDF8)),
            tooltip: _isScannerOpen ? 'Cerrar Escáner' : 'Abrir Escáner QR',
            onPressed: () => setState(() => _isScannerOpen = !_isScannerOpen),
          ),
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: _fetchPendingLifting,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF38BDF8)))
          : Column(
              children: [
                // Camera QR Scanner View (Collapsible)
                if (_isScannerOpen)
                  Container(
                    height: 220,
                    margin: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFF38BDF8), width: 2),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: Stack(
                      children: [
                        MobileScanner(
                          controller: _scannerController,
                          onDetect: (capture) {
                            final barcodes = capture.barcodes;
                            for (final barcode in barcodes) {
                              final rawVal = barcode.rawValue;
                              if (rawVal != null && rawVal.isNotEmpty) {
                                _onQrScanned(rawVal);
                              }
                            }
                          },
                        ),
                        Container(
                          alignment: Alignment.bottomCenter,
                          padding: const EdgeInsets.all(8),
                          color: const Color(0x99000000),
                          child: const Text('📷 Apunte la cámara al código QR del bulto',
                              style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),

                // Selected summary bar
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: const BoxDecoration(
                    color: Color(0xFF1E293B),
                    border: Border(bottom: BorderSide(color: Colors.white12)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Bultos por Recibir: ${_selectedIds.length} / ${_pendingBultos.length}',
                              style: GoogleFonts.plusJakartaSans(
                                  color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                          Text('Peso Total: ${_selectedWeight.toStringAsFixed(2)} Kg (${_selectedPkgs.toStringAsFixed(0)} unidades)',
                              style: const TextStyle(color: Color(0xFF38BDF8), fontSize: 12)),
                        ],
                      ),
                      Row(
                        children: [
                          TextButton(
                            onPressed: () {
                              setState(() {
                                if (_selectedIds.length == _pendingBultos.length) {
                                  _selectedIds.clear();
                                } else {
                                  _selectedIds.addAll(_pendingBultos.map((b) => b['id'] as int));
                                }
                              });
                            },
                            child: Text(
                              _selectedIds.length == _pendingBultos.length ? 'DESMARCAR' : 'TODOS',
                              style: const TextStyle(color: Color(0xFF38BDF8), fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                // List of ready bultos
                Expanded(
                  child: _pendingBultos.isEmpty
                      ? Center(
                          child: Padding(
                            padding: const EdgeInsets.all(24),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.all_inbox_rounded, size: 64, color: Colors.white24),
                                const SizedBox(height: 12),
                                Text('No hay bultos pendientes de levantar.',
                                    style: GoogleFonts.plusJakartaSans(color: Colors.white54, fontSize: 14)),
                                const SizedBox(height: 6),
                                Text('Cuando el Jefe de Operaciones apruebe bultos en fábrica, aparecerán aquí.',
                                    textAlign: TextAlign.center,
                                    style: GoogleFonts.plusJakartaSans(color: Colors.white30, fontSize: 12)),
                              ],
                            ),
                          ),
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.all(12),
                          itemCount: _pendingBultos.length,
                          separatorBuilder: (context, index) => const SizedBox(height: 8),
                          itemBuilder: (ctx, idx) {
                            final bulto = _pendingBultos[idx];
                            final id = bulto['id'] as int;
                            final isSelected = _selectedIds.contains(id);

                            return InkWell(
                              onTap: () {
                                setState(() {
                                  if (isSelected) {
                                    _selectedIds.remove(id);
                                  } else {
                                    _selectedIds.add(id);
                                  }
                                });
                              },
                              borderRadius: BorderRadius.circular(12),
                              child: Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: isSelected ? const Color(0xFF0F2E4A) : const Color(0xFF1C2541),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: isSelected ? const Color(0xFF38BDF8) : Colors.white10,
                                    width: isSelected ? 1.5 : 1,
                                  ),
                                ),
                                child: Row(
                                  children: [
                                    Checkbox(
                                      value: isSelected,
                                      activeColor: const Color(0xFF38BDF8),
                                      checkColor: const Color(0xFF0F172A),
                                      onChanged: (val) {
                                        setState(() {
                                          if (val == true) {
                                            _selectedIds.add(id);
                                          } else {
                                            _selectedIds.remove(id);
                                          }
                                        });
                                      },
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                            children: [
                                              Text(bulto['product_name'] ?? 'Bolsa',
                                                  style: GoogleFonts.plusJakartaSans(
                                                      color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold)),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF0F172A),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(bulto['qr_code'] ?? '',
                                                    style: const TextStyle(
                                                        color: Color(0xFF38BDF8), fontSize: 10, fontWeight: FontWeight.bold)),
                                              ),
                                            ],
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            'Operario: ${bulto['operator_name']}  •  Auditado por: ${bulto['reviewed_by']}',
                                            style: const TextStyle(color: Colors.white60, fontSize: 11),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            '${bulto['quantity']} bulto(s)  •  ${bulto['weight']} Kg',
                                            style: const TextStyle(
                                                color: Color(0xFF10B981), fontSize: 13, fontWeight: FontWeight.bold),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                ),

                // Bottom confirmation button
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: const BoxDecoration(
                    color: Color(0xFF1C2541),
                    border: Border(top: BorderSide(color: Colors.white12)),
                  ),
                  child: SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF059669),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 4,
                      ),
                      icon: _isSubmitting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.archive_rounded),
                      label: Text(
                        _isSubmitting
                            ? 'INGRESANDO ALMACÉN...'
                            : 'CONFIRMAR RECEPCIÓN E INGRESAR AL POS (${_selectedIds.length})',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                      onPressed: _isSubmitting || _selectedIds.isEmpty ? null : _submitLifting,
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}