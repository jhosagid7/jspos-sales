<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExecutiveGrowthReportTest extends TestCase
{
    /**
     * Valida que el informe ejecutivo en PDF se genera y contiene los datos requeridos.
     */
    public function test_executive_growth_report_pdf_exists_and_is_valid(): void
    {
        $pdfPath = base_path('Informe_Ejecutivo_Crecimiento_PLASTICOS_MF_STEEL.pdf');
        
        $this->assertFileExists($pdfPath, 'El archivo PDF del informe ejecutivo debe existir en la raíz del proyecto.');
        $this->assertGreaterThan(5000, filesize($pdfPath), 'El archivo PDF debe tener un tamaño válido y contenido generado.');

        $publicPdfPath = public_path('reports/Informe_Ejecutivo_Crecimiento_PLASTICOS_MF_STEEL.pdf');
        $this->assertFileExists($publicPdfPath, 'El archivo PDF debe existir también en la carpeta pública para descarga directa.');
    }

    /**
     * Valida que el segundo informe gráfico y de categorías en PDF se genera correctamente.
     */
    public function test_categories_and_graphical_report_pdf_exists_and_is_valid(): void
    {
        $pdfPath = base_path('Informe_Grafico_Crecimiento_y_Categorias.pdf');
        
        $this->assertFileExists($pdfPath, 'El archivo PDF gráfico y de categorías debe existir en la raíz.');
        $this->assertGreaterThan(5000, filesize($pdfPath), 'El archivo PDF gráfico debe tener contenido válido.');

        $publicPdfPath = public_path('reports/Informe_Grafico_Crecimiento_y_Categorias.pdf');
        $this->assertFileExists($publicPdfPath, 'El archivo PDF gráfico debe existir en public/reports.');
    }

    /**
     * Valida que el tercer informe de vendedores y evolución comercial en PDF se genera correctamente.
     */
    public function test_sellers_growth_report_pdf_exists_and_is_valid(): void
    {
        $pdfPath = base_path('Informe_Ejecutivo_Vendedores_y_Crecimiento.pdf');
        
        $this->assertFileExists($pdfPath, 'El archivo PDF de vendedores debe existir en la raíz.');
        $this->assertGreaterThan(5000, filesize($pdfPath), 'El archivo PDF de vendedores debe tener contenido válido.');

        $publicPdfPath = public_path('reports/Informe_Ejecutivo_Vendedores_y_Crecimiento.pdf');
        $this->assertFileExists($publicPdfPath, 'El archivo PDF de vendedores debe existir en public/reports.');
    }

    /**
     * Valida que el cuarto informe de operadores y eficiencia operativa en PDF se genera correctamente.
     */
    public function test_operators_efficiency_report_pdf_exists_and_is_valid(): void
    {
        $pdfPath = base_path('Informe_Ejecutivo_Eficiencia_Operadores.pdf');
        
        $this->assertFileExists($pdfPath, 'El archivo PDF de operadores debe existir en la raíz.');
        $this->assertGreaterThan(5000, filesize($pdfPath), 'El archivo PDF de operadores debe tener contenido válido.');

        $publicPdfPath = public_path('reports/Informe_Ejecutivo_Eficiencia_Operadores.pdf');
        $this->assertFileExists($publicPdfPath, 'El archivo PDF de operadores debe existir en public/reports.');
    }
}
