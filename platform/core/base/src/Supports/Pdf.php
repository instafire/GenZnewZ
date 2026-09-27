<?php

namespace Botble\Base\Supports;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPDF;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Closure;
use Dompdf\Adapter\CPDF;
use Dompdf\Image\Cache;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Throwable;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Twig\Extension\DebugExtension;

class Pdf
{
    protected string $templatePath;

    protected ?string $destinationPath = null;

    protected array|string $paperSize;

    protected string $content;

    protected array $data = [];

    protected ?string $supportLanguage = null;

    protected ?Closure $formatContentUsing = null;

    protected array $twigExtensions = [];

    protected string $processingLibrary = 'dompdf';

    public function templatePath(string $templatePath): static
    {
        $this->templatePath = $templatePath;

        return $this;
    }

    public function destinationPath(string $destinationPath): static
    {
        $this->destinationPath = $destinationPath;

        return $this;
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function paperSize(array|string $paperSize): static
    {
        $this->paperSize = $paperSize;

        return $this;
    }

    public function paperSizeA4(): static
    {
        return $this->paperSize(CPDF::$PAPER_SIZES['a4']);
    }

    public function paperSizeHalfLetter(): static
    {
        return $this->paperSize(CPDF::$PAPER_SIZES['half-letter']);
    }

    public function supportLanguage(string $language): static
    {
        $this->supportLanguage = $language;

        return $this;
    }

    public function supportArabic(): static
    {
        return $this->supportLanguage('arabic');
    }

    public function formatContentUsing(Closure $closure): static
    {
        $this->formatContentUsing = $closure;

        return $this;
    }

    public function twigExtensions(array $extensions): static
    {
        $this->twigExtensions = $extensions;

        return $this;
    }

    public function compile(): DomPDF
    {
        // Check if libxml extension is available
        if (! extension_loaded('libxml')) {
            throw new \Exception('The libxml extension is required for PDF generation. Please install the PHP libxml extension or switch to mPDF in your settings.');
        }

        $fontsPath = storage_path('fonts');

        if (! File::isDirectory($fontsPath)) {
            File::makeDirectory($fontsPath);
        }

        $this->content = $this->getContent($this->templatePath, $this->destinationPath, true);

        Cache::$error_message = null;

        return PdfFacade::setWarnings(false)
            ->setOption('chroot', [public_path(), base_path()])
            ->setOption('tempDir', storage_path('app'))
            ->setOption('logOutputFile', false)
            ->setOption('isRemoteEnabled', true)
            ->loadHTML($this->content, 'UTF-8')
            ->setPaper($this->paperSize ?? CPDF::$PAPER_SIZES['a4']);
    }

    public function getContent(string $templatePath, ?string $customizedPath = null, bool $compiled = false): string
    {
        if (! $customizedPath) {
            $customizedPath = storage_path('app/templates/' . basename($templatePath));
        }

        if (File::exists($customizedPath)) {
            $content = BaseHelper::getFileData($customizedPath, false);
        } else {
            $content = File::exists($templatePath) ? BaseHelper::getFileData($templatePath, false) : '';
        }

        $content = (string) $content;

        if ($content && $compiled) {
            $defaultData = [
                'settings' => [
                    'font_family' => apply_filters('pdf_font_family', 'DejaVu Sans'),
                    'font_css' => apply_filters('pdf_font_css', null),
                    'extra_css' => apply_filters('pdf_extra_css', null),
                    'header_html' => apply_filters('pdf_header_html', null),
                    'footer_html' => apply_filters('pdf_footer_html', null),
                ],
            ];

            $data = [...$defaultData, ...$this->data];

            switch ($this->supportLanguage) {
                case 'bangladesh':
                    $data['settings']['font_family'] = 'FreeSerif';
                    $data['settings']['header_html'] .= view('core/base::pdf.style-bangladesh')->render();

                    break;
                case 'chinese':
                    $data['settings']['font_family'] = 'msyh';
                    $data['settings']['header_html'] .= view('core/base::pdf.style-chinese')->render();

                    break;
            }

            $content = $this->compileContent($content, $data);

            if ($this->formatContentUsing) {
                $content = call_user_func($this->formatContentUsing, $content);
            }

            if ($this->getProcessingLibrary() == 'dompdf') {
                $currencies = [
                    '₼' => 'azeri-manat',
                    '₹' => 'indian-rupee',
                    '৳' => 'bangladeshi-taka',
                    '₺' => 'turkish-lira',
                    '﷼' => 'iranian-rial',
                ];

                if ($this->supportLanguage === 'arabic') {
                    $content = $this->compileArabic($content);
                }
            } else {
                $currencies = [
                    '﷼' => 'iranian-rial',
                ];
            }

            foreach ($currencies as $currency => $icon) {
                $content = str_replace(
                    $currency,
                    Html::image(asset("vendor/core/core/base/images/pdf-symbols/$icon.svg"), 'currency', ['width' => 10, 'style' => 'margin-right: 2px; display: inline-block;']),
                    $content
                );
            }
        }

        return $content;
    }

    protected function compileContent(string $content, array $data = []): string
    {
        $twigCompiler = new TwigCompiler([
            'autoescape' => false,
            'debug' => true,
        ]);

        $twigCompiler->addExtension(new DebugExtension());

        foreach ($this->twigExtensions as $extension) {
            $twigCompiler->addExtension($extension);
        }

        return $twigCompiler->compile($content, $data);
    }

    protected function compileArabic(string $content): string
    {
        if (! class_exists(Arabic::class)) {
            return $content;
        }

        $arabic = new Arabic();
        $p = $arabic->arIdentify($content);

        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            try {
                $utf8ar = $arabic->utf8Glyphs(substr($content, $p[$i - 1], $p[$i] - $p[$i - 1]));
                $content = substr_replace($content, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
            } catch (Throwable) {
                continue;
            }
        }

        return $content;
    }

    public function setProcessingLibrary(string $library): static
    {
        $this->processingLibrary = $library;

        return $this;
    }

    public function getProcessingLibrary(): string
    {
        return $this->processingLibrary;
    }

    public function compileMpdf(string $fileName, string $mode = 'D'): ?string
    {
        $format = $this->convertPaperSizeForMpdf($this->paperSize ?? 'A4');

        $config = [
            'mode' => 'utf-8',
            'format' => $format,
            'tempDir' => storage_path('app'),
        ];

        $mpdf = new Mpdf($config);

        $mpdf->autoLangToFont = true;

        $inlineCss = new CssToInlineStyles();

        $content = $this->getContent($this->templatePath, $this->destinationPath, true);

        $content = $inlineCss->convert($content);

        $mpdf->WriteHTML($content);

        return $mpdf->Output($fileName, $mode);
    }

    protected function convertPaperSizeForMpdf(array|string $paperSize): array|string
    {
        if (is_string($paperSize)) {
            return $paperSize;
        }

        if (is_array($paperSize) && count($paperSize) === 4) {
            $widthMm = ($paperSize[2] - $paperSize[0]) * 0.352778;
            $heightMm = ($paperSize[3] - $paperSize[1]) * 0.352778;

            return [round($widthMm, 2), round($heightMm, 2)];
        }

        if (is_array($paperSize) && count($paperSize) === 2) {
            return $paperSize;
        }

        return 'A4';
    }

    public function stream(string $fileName = 'document.pdf'): Response|string|null
    {
        if ($this->getProcessingLibrary() == 'mpdf' || ! extension_loaded('libxml')) {
            return $this->compileMpdf($fileName, 'I');
        }

        return $this->compile()->stream($fileName);
    }

    public function download(string $fileName): Response|string|null
    {
        if ($this->getProcessingLibrary() == 'mpdf' || ! extension_loaded('libxml')) {
            return $this->compileMpdf($fileName);
        }

        return $this->compile()->download($fileName);
    }

    public function save(string $filePath): DomPDF|string|null
    {
        if ($this->getProcessingLibrary() == 'mpdf' || ! extension_loaded('libxml')) {
            return $this->compileMpdf($filePath, 'F');
        }

        return $this->compile()->save($filePath);
    }
}
