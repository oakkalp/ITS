<?php
/**
 * Basit TCPDF Sınıfı
 * PDF oluşturma için minimal implementasyon
 */

class TCPDF {
    private $page_orientation = 'P';
    private $unit = 'mm';
    private $page_format = 'A4';
    private $encoding = 'UTF-8';
    private $unicode = true;
    private $diskcache = false;
    private $pdfa = false;
    
    private $title = '';
    private $subject = '';
    private $author = '';
    private $creator = '';
    
    private $margins = ['left' => 15, 'top' => 20, 'right' => 15, 'bottom' => 15];
    private $header_margin = 5;
    private $footer_margin = 10;
    private $auto_page_break = true;
    private $page_break_margin = 15;
    
    private $font_family = 'helvetica';
    private $font_size = 10;
    private $font_style = '';
    
    private $current_page = 0;
    private $content = '';
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
        $this->page_orientation = $orientation;
        $this->unit = $unit;
        $this->page_format = $format;
        $this->unicode = $unicode;
        $this->encoding = $encoding;
        $this->diskcache = $diskcache;
        $this->pdfa = $pdfa;
    }
    
    public function SetCreator($creator) {
        $this->creator = $creator;
    }
    
    public function SetAuthor($author) {
        $this->author = $author;
    }
    
    public function SetTitle($title) {
        $this->title = $title;
    }
    
    public function SetSubject($subject) {
        $this->subject = $subject;
    }
    
    public function SetMargins($left, $top, $right = null) {
        $this->margins['left'] = $left;
        $this->margins['top'] = $top;
        $this->margins['right'] = $right ?: $left;
    }
    
    public function SetHeaderMargin($margin) {
        $this->header_margin = $margin;
    }
    
    public function SetFooterMargin($margin) {
        $this->footer_margin = $margin;
    }
    
    public function SetAutoPageBreak($auto, $margin = 0) {
        $this->auto_page_break = $auto;
        $this->page_break_margin = $margin;
    }
    
    public function SetFont($family, $style = '', $size = 0) {
        $this->font_family = $family;
        $this->font_style = $style;
        if ($size > 0) {
            $this->font_size = $size;
        }
    }
    
    public function SetDefaultMonospacedFont($font) {
        // Basit implementasyon - gerçek TCPDF'de font ayarları yapılır
        $this->font_family = $font;
    }
    
    public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false) {
        $this->current_page++;
        $this->content .= "\n<!-- Page " . $this->current_page . " -->\n";
    }
    
    public function writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '') {
        // HTML'i basit şekilde işle
        $html = strip_tags($html, '<b><i><u><br><p><div><span><table><tr><td><th><h1><h2><h3><h4><h5><h6>');
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace(['<b>', '</b>'], ['**', '**'], $html);
        $html = str_replace(['<i>', '</i>'], ['*', '*'], $html);
        $html = str_replace(['<u>', '</u>'], ['__', '__'], $html);
        $html = preg_replace('/<[^>]+>/', '', $html);
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        $this->content .= $html;
        if ($ln) {
            $this->content .= "\n";
        }
    }
    
    public function Output($name = 'doc.pdf', $dest = 'I') {
        // Basit HTML çıktısı oluştur
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: avoid; }
        h1, h2, h3 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <div class="page">
        ' . nl2br(htmlspecialchars($this->content)) . '
    </div>
</body>
</html>';
        
        if ($dest === 'D') {
            // Download
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            echo $html;
        } elseif ($dest === 'I') {
            // Inline
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
        } else {
            return $html;
        }
    }
}

// Sabitler
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_PAGE_FORMAT', 'A4');
define('PDF_FONT_MONOSPACED', 'courier');
?>
