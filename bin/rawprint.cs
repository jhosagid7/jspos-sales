using System;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Runtime.InteropServices;
using System.Diagnostics;

public class RawPrintHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFOW {
        [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPWStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOW di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static string ResolveHostToIp(string host) {
        if (string.IsNullOrEmpty(host)) return host;
        IPAddress ip;
        if (IPAddress.TryParse(host, out ip)) return host;
        try {
            var addresses = Dns.GetHostAddresses(host);
            if (addresses != null && addresses.Length > 0) {
                foreach (var a in addresses) {
                    if (a.AddressFamily == AddressFamily.InterNetwork) {
                        return a.ToString();
                    }
                }
            }
        } catch {}
        return host;
    }

    public static bool TestTcpPort(string host, int port, int timeoutMs) {
        string target = ResolveHostToIp(host);
        try {
            using (var client = new TcpClient()) {
                var ar = client.BeginConnect(target, port, null, null);
                if (ar.AsyncWaitHandle.WaitOne(timeoutMs)) {
                    client.EndConnect(ar);
                    return true;
                }
            }
        } catch {}
        return false;
    }

    public static List<string> ScanSubnetLiveIps(string subnetPrefix, int timeoutMs) {
        var liveIps = new List<string>();
        var tasks = new List<Task>();
        object lockObj = new object();

        for (int i = 1; i <= 254; i++) {
            string ip = subnetPrefix + i;
            tasks.Add(Task.Factory.StartNew(() => {
                if (TestTcpPort(ip, 445, timeoutMs)) {
                    lock (lockObj) {
                        liveIps.Add(ip);
                    }
                }
            }));
        }

        Task.WaitAll(tasks.ToArray());
        return liveIps;
    }

    public static bool TrySpoolerPrint(string pName, byte[] bytes) {
        IntPtr hPrn = IntPtr.Zero;
        DOCINFOW di = new DOCINFOW();
        di.pDocName = "POS Ticket";
        di.pDataType = "RAW";

        bool ok = false;
        try {
            if (OpenPrinter(pName, out hPrn, IntPtr.Zero)) {
                if (StartDocPrinter(hPrn, 1, di)) {
                    if (StartPagePrinter(hPrn)) {
                        IntPtr p = Marshal.AllocCoTaskMem(bytes.Length);
                        Marshal.Copy(bytes, 0, p, bytes.Length);
                        Int32 written = 0;
                        ok = WritePrinter(hPrn, p, bytes.Length, out written);
                        Marshal.FreeCoTaskMem(p);
                        EndPagePrinter(hPrn);
                    }
                    EndDocPrinter(hPrn);
                }
                ClosePrinter(hPrn);
            }
        } catch {}
        return ok;
    }

    public static bool TryDirectSmbPrint(string uncPath, byte[] bytes) {
        try {
            File.WriteAllBytes(uncPath, bytes);
            return true;
        } catch {
            return false;
        }
    }

    public static int Main(string[] args) {
        if (args.Length == 0) {
            Console.WriteLine("Usage: rawprint <printer_name> <file_path>");
            Console.WriteLine("       rawprint --test <printer_name> [user] [pass]");
            Console.WriteLine("       rawprint --scan-subnet <subnet_prefix>");
            return 2;
        }

        // Mode 1: Subnet Live IP Scan
        if (args[0] == "--scan-subnet") {
            string prefix = args.Length > 1 ? args[1] : "192.168.20.";
            if (!prefix.EndsWith(".")) prefix += ".";
            var live = ScanSubnetLiveIps(prefix, 350);
            Console.WriteLine(string.Join(",", live.ToArray()));
            return 0;
        }

        // Mode 2: Test Printer Connection
        if (args[0] == "--test") {
            if (args.Length < 2) {
                Console.WriteLine("Error: Missing printer name");
                return 2;
            }
            string printerName = args[1];
            string user = args.Length > 2 ? args[2] : "";
            string pass = args.Length > 3 ? args[3] : "";

            if (printerName.StartsWith("\\\\")) {
                string clean = printerName.Substring(2);
                int slashIdx = clean.IndexOf('\\');
                string host = slashIdx > 0 ? clean.Substring(0, slashIdx) : clean;
                string share = slashIdx > 0 ? clean.Substring(slashIdx + 1) : "";

                // 1. Probe host on port 445
                bool tcpOk = TestTcpPort(host, 445, 500);
                if (!tcpOk) {
                    Console.WriteLine("Error: El equipo de red (" + host + ") no responde o esta apagado.");
                    return 1;
                }

                // 2. If user/pass provided, authenticate session
                if (!string.IsNullOrEmpty(user)) {
                    try {
                        string netArgs = "use \\\\" + host + " /u:\"" + user + "\"";
                        if (!string.IsNullOrEmpty(pass)) {
                            netArgs += " \"" + pass + "\"";
                        }
                        var psi = new ProcessStartInfo("net", netArgs) {
                            RedirectStandardOutput = true,
                            RedirectStandardError = true,
                            UseShellExecute = false,
                            CreateNoWindow = true
                        };
                        using (var p = Process.Start(psi)) {
                            p.WaitForExit(2000);
                        }
                    } catch {}
                }

                // 3. Test OpenPrinter or Direct SMB write handle
                IntPtr hPrinter = IntPtr.Zero;
                bool opened = OpenPrinter(printerName, out hPrinter, IntPtr.Zero);
                if (opened) {
                    ClosePrinter(hPrinter);
                    Console.WriteLine("OK");
                    return 0;
                }

                // Also test if raw IP UNC works if host was a name
                string ipHost = ResolveHostToIp(host);
                if (ipHost != host) {
                    string ipUnc = "\\\\" + ipHost + "\\" + share;
                    if (OpenPrinter(ipUnc, out hPrinter, IntPtr.Zero)) {
                        ClosePrinter(hPrinter);
                        Console.WriteLine("OK");
                        return 0;
                    }
                }

                // Test SMB share direct stream access
                try {
                    using (var fs = new FileStream(printerName, FileMode.OpenOrCreate, FileAccess.Write, FileShare.ReadWrite)) {
                        Console.WriteLine("OK");
                        return 0;
                    }
                } catch {}

                if (ipHost != host) {
                    string ipUnc = "\\\\" + ipHost + "\\" + share;
                    try {
                        using (var fs = new FileStream(ipUnc, FileMode.OpenOrCreate, FileAccess.Write, FileShare.ReadWrite)) {
                            Console.WriteLine("OK");
                            return 0;
                        }
                    } catch {}
                }

                int err = Marshal.GetLastWin32Error();
                Console.WriteLine("Error: Nombre de impresora no encontrado en el equipo de red (Error " + (err > 0 ? err.ToString() : "1801") + ").");
                return 1;
            } else {
                // Local printer test
                IntPtr hPrinter = IntPtr.Zero;
                bool opened = OpenPrinter(printerName, out hPrinter, IntPtr.Zero);
                if (opened) {
                    ClosePrinter(hPrinter);
                    Console.WriteLine("OK");
                    return 0;
                } else {
                    int err = Marshal.GetLastWin32Error();
                    Console.WriteLine("Error: No se pudo abrir la impresora local (Error " + err + ")");
                    return 1;
                }
            }
        }

        // Mode 3: Print File
        string pName = args[0];
        string filePath = args[1];

        if (!File.Exists(filePath)) {
            Console.WriteLine("Error: File not found: " + filePath);
            return 3;
        }

        byte[] bytes;
        try {
            bytes = File.ReadAllBytes(filePath);
        } catch (Exception ex) {
            Console.WriteLine("Error reading file: " + ex.Message);
            return 4;
        }

        // 1. Try Windows Spooler API
        if (TrySpoolerPrint(pName, bytes)) {
            Console.WriteLine("OK");
            return 0;
        }

        // 2. If network UNC, try resolving hostname to IP and try Spooler again
        if (pName.StartsWith("\\\\")) {
            string clean = pName.Substring(2);
            int slashIdx = clean.IndexOf('\\');
            string ipUnc = null;
            if (slashIdx > 0) {
                string host = clean.Substring(0, slashIdx);
                string share = clean.Substring(slashIdx + 1);
                string ip = ResolveHostToIp(host);
                if (ip != host) {
                    ipUnc = "\\\\" + ip + "\\" + share;
                    if (TrySpoolerPrint(ipUnc, bytes)) {
                        Console.WriteLine("OK");
                        return 0;
                    }
                }
            }

            // 3. Fallback: Direct SMB Write
            if (TryDirectSmbPrint(pName, bytes)) {
                Console.WriteLine("OK");
                return 0;
            }

            if (!string.IsNullOrEmpty(ipUnc) && TryDirectSmbPrint(ipUnc, bytes)) {
                Console.WriteLine("OK");
                return 0;
            }
        }

        int lastErr = Marshal.GetLastWin32Error();
        Console.WriteLine("Error printing (Win32 error: " + lastErr + ")");
        return 1;
    }
}
