<?php

class SimpleDocx {
    private $parts = [];
    private $relationships = [];
    private $images = [];
    private $imageCounter = 0;
    private $body = '';
    private $numbering = '';
    private $hasNumbering = false;

    public function addHeading($text, $level = 1) {
        $size = match($level) {
            1 => 32,
            2 => 26,
            3 => 24,
            4 => 22,
            default => 20
        };
        $bold = $level <= 3 ? '<w:b/><w:bCs/>' : '';
        $color = $level <= 2 ? '<w:color w:val="1a1a1a"/>' : '';
        $spacing = $level == 1 ? '<w:spacing w:before="360" w:after="200"/>' : '<w:spacing w:before="240" w:after="120"/>';

        $this->body .= '<w:p><w:pPr>' . $spacing . '<w:jc w:val="left"/></w:pPr><w:r><w:rPr>' . $bold . $color . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/></w:rPr><w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addTitle($text) {
        $this->body .= '<w:p><w:pPr><w:spacing w:before="480" w:after="240"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:bCs/><w:sz w:val="48"/><w:szCs w:val="48"/><w:color w:val="DC2626"/></w:rPr><w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addSubtitle($text) {
        $this->body .= '<w:p><w:pPr><w:spacing w:before="120" w:after="360"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="28"/><w:szCs w:val="28"/><w:color w:val="666666"/></w:rPr><w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addCenteredText($text, $size = 22, $bold = false) {
        $b = $bold ? '<w:b/><w:bCs/>' : '';
        $this->body .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr>' . $b . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/></w:rPr><w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addParagraph($text, $bold = false, $indent = false) {
        $b = $bold ? '<w:b/><w:bCs/>' : '';
        $ind = $indent ? '<w:ind w:left="720"/>' : '';
        $this->body .= '<w:p><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/><w:jc w:val="both"/>' . $ind . '</w:pPr><w:r><w:rPr>' . $b . '<w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addBullet($text, $level = 0) {
        $indent = 720 + ($level * 360);
        $hanging = 360;
        $bullet = $level == 0 ? "\xe2\x80\xa2" : "\xe2\x97\xa6";
        $this->body .= '<w:p><w:pPr><w:spacing w:after="60" w:line="276" w:lineRule="auto"/><w:ind w:left="' . $indent . '" w:hanging="' . $hanging . '"/></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t xml:space="preserve">' . $bullet . '  ' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addNumberedItem($number, $text) {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="60" w:line="276" w:lineRule="auto"/><w:ind w:left="720" w:hanging="360"/></w:pPr><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t xml:space="preserve">' . $number . '. ' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addTable($headers, $rows) {
        $colCount = count($headers);
        $colWidth = intval(9000 / $colCount);

        $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>';
        $xml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>';
        $xml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>';
        $xml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>';
        $xml .= '</w:tblBorders><w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/></w:tblPr>';

        $xml .= '<w:tblGrid>';
        for ($i = 0; $i < $colCount; $i++) {
            $xml .= '<w:gridCol w:w="' . $colWidth . '"/>';
        }
        $xml .= '</w:tblGrid>';

        $xml .= '<w:tr>';
        foreach ($headers as $h) {
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="DC2626"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:bCs/><w:color w:val="FFFFFF"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve">' . $this->esc($h) . '</w:t></w:r></w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        $rowIndex = 0;
        foreach ($rows as $row) {
            $fill = ($rowIndex % 2 == 0) ? 'FFFFFF' : 'F8F5F0';
            $xml .= '<w:tr>';
            foreach ($row as $cell) {
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/></w:tcPr>';
                $xml .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve">' . $this->esc($cell) . '</w:t></w:r></w:p></w:tc>';
            }
            $xml .= '</w:tr>';
            $rowIndex++;
        }

        $xml .= '</w:tbl>';
        $this->body .= $xml;
        $this->addEmptyLine();
    }

    public function addEmptyLine() {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr></w:p>';
    }

    public function addPageBreak() {
        $this->body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    public function addHorizontalLine() {
        $this->body .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="e0d6c8"/></w:pBdr></w:pPr></w:p>';
    }

    private function esc($text) {
        return htmlspecialchars($text, ENT_XML1, 'UTF-8');
    }

    public function save($filename) {
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create file: $filename");
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');

        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
xmlns:mo="http://schemas.microsoft.com/office/mac/office/2008/main"
xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
xmlns:mv="urn:schemas-microsoft-com:mac:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"
xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
xmlns:w10="urn:schemas-microsoft-com:office:word"
xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"
xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"
xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"
mc:Ignorable="w14 wp14">
<w:body>
<w:sectPr>
<w:pgSz w:w="12240" w:h="15840"/>
<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
<w:cols w:space="720"/>
<w:docGrid w:linePitch="360"/>
</w:sectPr>
' . $this->body . '
</w:body>
</w:document>';

        $zip->addFromString('word/document.xml', $document);

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:docDefaults>
<w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:eastAsia="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/><w:sz w:val="22"/><w:szCs w:val="22"/><w:lang w:val="en-US"/></w:rPr></w:rPrDefault>
<w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault>
</w:docDefaults>
<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/></w:style>
</w:styles>';

        $zip->addFromString('word/styles.xml', $styles);

        $zip->close();
        return true;
    }
}

$doc = new SimpleDocx();

$doc->addEmptyLine();
$doc->addEmptyLine();
$doc->addEmptyLine();
$doc->addTitle('SECONDPLAN');
$doc->addSubtitle('Band Management and Event Booking Platform');
$doc->addEmptyLine();
$doc->addHorizontalLine();
$doc->addEmptyLine();
$doc->addCenteredText('CAPSTONE PROJECT TECHNICAL REPORT', 28, true);
$doc->addEmptyLine();
$doc->addEmptyLine();
$doc->addCenteredText('Prepared by:', 22);
$doc->addCenteredText('[Student Name]', 24, true);
$doc->addCenteredText('[Student ID]', 22);
$doc->addEmptyLine();
$doc->addCenteredText('Supervisor:', 22);
$doc->addCenteredText('[Supervisor Name]', 24, true);
$doc->addEmptyLine();
$doc->addEmptyLine();
$doc->addCenteredText('Faculty of Computer Science and Information Technology', 22);
$doc->addCenteredText('[University Name]', 22);
$doc->addCenteredText('2026', 22);

$doc->addPageBreak();

$doc->addHeading('TABLE OF CONTENTS', 1);
$doc->addEmptyLine();
$doc->addParagraph('CHAPTER 1: INTRODUCTION');
$doc->addParagraph('   1.1 System Background and Context', false, true);
$doc->addParagraph('   1.2 Problem Statements', false, true);
$doc->addParagraph('   1.3 System Objectives', false, true);
$doc->addParagraph('   1.4 Scope', false, true);
$doc->addEmptyLine();
$doc->addParagraph('CHAPTER 2: LITERATURE SURVEY & BENCHMARKING');
$doc->addParagraph('   2.1 Literature Review', false, true);
$doc->addParagraph('   2.2 Benchmarking', false, true);
$doc->addEmptyLine();
$doc->addParagraph('CHAPTER 3: METHODOLOGY');
$doc->addParagraph('   3.1 System Design', false, true);
$doc->addParagraph('   3.2 Database Design', false, true);
$doc->addParagraph('   3.3 Data Structure', false, true);
$doc->addEmptyLine();
$doc->addParagraph('CHAPTER 4: PROJECT OUTCOME / DESIGN AND IMPLEMENTATION');
$doc->addParagraph('   4.1 System Design', false, true);
$doc->addParagraph('   4.2 Database Design', false, true);
$doc->addParagraph('   4.3 Data Dictionary', false, true);
$doc->addEmptyLine();
$doc->addParagraph('CHAPTER 5: CONCLUSION');
$doc->addParagraph('   5.1 Significance and Contributions', false, true);
$doc->addParagraph('   5.2 Limitations', false, true);
$doc->addParagraph('   5.3 Future Enhancement', false, true);
$doc->addEmptyLine();
$doc->addParagraph('REFERENCES');
$doc->addParagraph('APPENDICES');

$doc->addPageBreak();

$doc->addHeading('CHAPTER 1: INTRODUCTION', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addHeading('1.1 System Background and Context', 2);
$doc->addParagraph('Independent bands and musical groups in Malaysia often operate without a centralized digital system, relying heavily on manual processes to manage their daily operations. Tasks such as booking management, expense tracking, merchandise sales, task coordination, and communication with clients and fans are typically handled through a combination of WhatsApp messages, spreadsheets, social media platforms, and verbal agreements. This fragmented approach leads to inefficiencies, miscommunication, data loss, and missed business opportunities.');
$doc->addParagraph('The music industry in Malaysia has seen significant growth in the independent music scene, with more bands performing at corporate events, weddings, private parties, and public festivals. As the demand for professional live music services increases, so does the need for a structured operational system that can support the business side of running a band. Currently, most available platforms in the Malaysian market focus on event ticketing and promotion for audiences, rather than providing internal management tools for the performers themselves.');
$doc->addParagraph('The Second Plan System is a web-based platform specifically designed to address these operational challenges faced by independent bands. Built using PHP 8.x with MySQL database and vanilla JavaScript, the system provides a comprehensive management solution organized into three distinct role-based portals: an Admin Portal for the band manager, a Band Member Portal for musicians, and a Customer Portal for clients and fans. The system integrates event management, booking workflows with quotation-to-invoice processing, task assignment, expense tracking with receipt management, merchandise e-commerce with shopping cart and checkout, order fulfillment, real-time notifications, and comprehensive reporting into a single, unified platform.');
$doc->addParagraph('This capstone project demonstrates the practical application of web development technologies and database management principles to solve a real-world operational problem in the entertainment industry. The system serves as both a functional product for an actual Malaysian band and an academic exercise in full-stack web application development, covering areas such as authentication and security, role-based access control, RESTful API design, responsive UI/UX design, and database normalization.');

$doc->addHeading('1.2 Problem Statements', 2);
$doc->addParagraph('Independent bands such as the target user group face several critical operational challenges due to the absence of an integrated management system:');
$doc->addEmptyLine();
$doc->addParagraph('Problem 1: Inefficient Event and Task Management', true);
$doc->addParagraph('Bookings, schedules, and task assignments are currently handled manually through WhatsApp messages and spreadsheets, leading to double bookings, missed responsibilities, and weak coordination among band members. There is no centralized calendar to view all commitments, and no automated system to assign and track task completion. Band members frequently miss deadlines or show up unprepared because task communication is informal and untracked.');
$doc->addEmptyLine();
$doc->addParagraph('Problem 2: Limited Financial and Merchandise Oversight', true);
$doc->addParagraph('Expense tracking, receipt storage, reimbursement monitoring, and merchandise stock updates are done manually, increasing the risk of errors, missing records, and delays in accountability. Booking payments lack a formal quotation-to-invoice workflow, making it difficult to track outstanding payments and due dates. The band has no visibility into which expenses have been approved, which invoices are overdue, or what merchandise is running low on stock.');
$doc->addEmptyLine();
$doc->addParagraph('Problem 3: Lack of Structured Fan and Client Engagement', true);
$doc->addParagraph('Fans rely solely on social media without a dedicated platform for event updates and merchandise purchases, while clients have no centralized system to book performances, check date availability, or receive formal quotations and invoices. This results in lost engagement opportunities, unprofessional client interactions, and difficulty in converting inquiries into confirmed bookings.');

$doc->addHeading('1.3 System Objectives', 2);
$doc->addParagraph('The objectives of the Second Plan System are:');
$doc->addNumberedItem(1, 'To develop a centralized web-based system with three role-based portals (Admin, Band Member, Customer) for managing events, bookings, and tasks efficiently.');
$doc->addNumberedItem(2, 'To enhance financial oversight through a structured quotation-to-invoice booking workflow, expense approval pipeline, and merchandise order management with payment tracking.');
$doc->addNumberedItem(3, 'To improve customer accessibility through an integrated booking system with availability calendar, e-commerce merchandise shop with shopping cart, and automated notification system.');

$doc->addHeading('1.4 Scope', 2);
$doc->addParagraph('System Scope', true);
$doc->addParagraph('The Second Plan System encompasses the following functional modules:');
$doc->addBullet('Event Management: Create, update, cancel, and display events with details including date, time, venue, capacity, pricing, and poster images.');
$doc->addBullet('Booking and Invoice Management: Complete booking lifecycle from customer submission through admin approval/rejection, quotation and invoice generation, payment tracking, and receipt upload.');
$doc->addBullet('Task Management: Admin assigns tasks to band members with priority levels and due dates; band members update task status through their portal.');
$doc->addBullet('Expense Tracking: Band members submit expense claims with receipt uploads; admin approves or rejects claims.');
$doc->addBullet('Merchandise E-Commerce: Product catalog management, shopping cart, checkout with stock validation, and order fulfillment tracking.');
$doc->addBullet('Notification System: Real-time in-app notifications across all portals with polling-based updates.');
$doc->addBullet('Reports and Analytics: Financial overview, revenue charts, booking statistics, expense breakdowns, and merchandise sales data with CSV export.');
$doc->addBullet('Authentication and Security: Login with rate limiting, password reset, CSRF protection, role-based access control, and activity logging.');
$doc->addEmptyLine();
$doc->addParagraph('User Scope', true);
$doc->addParagraph('The system serves three distinct user groups:');

$doc->addTable(
    ['User Group', 'Portal', 'Key Capabilities'],
    [
        ['Admin (Band Manager)', '/admin/', 'Manage events, bookings (approve/reject with invoice generation), tasks (assign to band members), expenses (approve/reject), merchandise catalog, orders, users, settings, reports, and activity logs.'],
        ['Band Member', '/band/', 'View schedule calendar (FullCalendar), manage assigned tasks (update status), submit expenses with receipts, view band events, and edit profile.'],
        ['Customer', '/user/', 'Submit booking requests with availability calendar, browse and purchase merchandise (cart and checkout), view booking status and invoices, upload payment receipts, track orders, and edit profile.'],
    ]
);

$doc->addPageBreak();

$doc->addHeading('CHAPTER 2: LITERATURE SURVEY & BENCHMARKING', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addHeading('2.1 Literature Review', 2);
$doc->addParagraph('Independent bands in Malaysia often operate without structured digital systems, relying instead on a mixture of social media platforms, event websites, and manual record-keeping to manage their performances, fan engagement, and finances. A review of existing Malaysian platforms reveals that while these systems offer useful public-facing functions, none provide a dedicated, end-to-end operational solution for independent bands. This creates significant challenges in coordinating bookings, managing tasks, tracking expenses, and maintaining organized communication with fans and clients.');
$doc->addEmptyLine();

$doc->addParagraph('GoLive (Malaysia)', true);
$doc->addParagraph('GoLive is widely used for event ticketing and audience management in Malaysia. Bands often rely on this platform to promote shows and sell tickets. Although GoLive provides robust tools for registration, promotion, and participant analytics, it focuses primarily on public event management. It does not address internal band needs such as coordinating roles, uploading receipts, managing reimbursements, or tracking band-owned merchandise. Bands must therefore combine GoLive with additional manual workflows to complete their operational tasks.');
$doc->addEmptyLine();

$doc->addParagraph('Gigsmore', true);
$doc->addParagraph('Gigsmore is one of the few Malaysian platforms focused on connecting artists and event organizers. It simplifies the process of discovering gigs and allows performers to apply for available slots. However, its features are limited to gig listing and matching; it does not support internal band operations such as expense tracking, merchandise management, or task assignment. As a result, indie bands still handle most operational activities manually through WhatsApp, Excel, and social media announcements.');
$doc->addEmptyLine();

$doc->addParagraph('LOLAsia', true);
$doc->addParagraph('LOLAsia is a Malaysian entertainment ticketing and event promotion platform that offers a centralized space for events and performances, helping entertainers reach wider audiences. It is useful for fans who want to browse upcoming shows. However, it also lacks internal management features for artists or bands. There is no support for expense logging, band communication, multi-role collaboration, or merchandise monitoring, limiting its use to public-facing functions only.');
$doc->addEmptyLine();

$doc->addParagraph('The analysis reveals a clear gap in the Malaysian market: existing platforms support public promotion and ticketing but do not offer tools for internal management, which is essential for independent bands that lack structured administrative systems. The Second Plan System aims to fill this gap by providing an integrated solution that combines event management, booking workflows, task coordination, expense tracking, merchandise e-commerce, and role-based portals in a single system.');

$doc->addHeading('2.2 Benchmarking', 2);
$doc->addParagraph('The following benchmarking analysis compares the Second Plan System against three existing Malaysian platforms across key features, security, usability, and efficiency:');

$doc->addTable(
    ['Feature', 'Second Plan', 'GoLive', 'LOLAsia', 'Gigsmore'],
    [
        ['Event Management', 'YES', 'YES', 'YES', 'YES'],
        ['Booking with Quotation & Invoice', 'YES', 'NO', 'NO', 'NO'],
        ['Fan/Customer Engagement', 'YES', 'YES', 'YES', 'NO'],
        ['Merchandise E-Commerce', 'YES', 'NO', 'NO', 'NO'],
        ['Shopping Cart & Checkout', 'YES', 'NO', 'NO', 'NO'],
        ['Task Coordination', 'YES', 'NO', 'NO', 'NO'],
        ['Expense Tracking & Receipts', 'YES', 'NO', 'NO', 'NO'],
        ['Payment Tracking & Invoicing', 'YES', 'NO', 'NO', 'NO'],
        ['Role-Based Portals', 'YES (3 Portals)', 'NO', 'NO', 'NO'],
        ['Notification System', 'YES', 'YES', 'NO', 'NO'],
        ['Reports & Analytics', 'YES', 'YES', 'NO', 'NO'],
        ['Activity Audit Log', 'YES', 'NO', 'NO', 'NO'],
        ['CSRF Protection', 'YES', 'Unknown', 'Unknown', 'Unknown'],
        ['Login Rate Limiting', 'YES', 'Unknown', 'Unknown', 'Unknown'],
        ['Responsive Design', 'YES', 'YES', 'YES', 'YES'],
    ]
);

$doc->addParagraph('Key Findings:', true);
$doc->addBullet('The Second Plan System is the only platform that provides a complete internal management solution specifically designed for independent bands.');
$doc->addBullet('Existing platforms serve different purposes (ticketing, gig matching, promotion) but none address the full operational lifecycle of running a band.');
$doc->addBullet('Security features such as CSRF protection, login rate limiting, and activity audit logging are unique to the Second Plan System among the compared platforms.');
$doc->addBullet('The three-portal architecture (Admin, Band Member, Customer) is a differentiating feature that ensures each user group has access to only the tools relevant to their role.');

$doc->addPageBreak();

$doc->addHeading('CHAPTER 3: METHODOLOGY', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addParagraph('The project follows the Agile methodology with iterative development across multiple development sessions. Agile was chosen as the most suitable methodology for this project for the following reasons:');
$doc->addEmptyLine();
$doc->addBullet('Incremental Delivery: The system consists of multiple independent modules (events, bookings, merchandise, tasks, expenses) that can be developed and tested individually before integration.');
$doc->addBullet('Adaptability: Requirements evolved during development as real-world testing revealed new needs (e.g., adding payment receipt uploads, availability calendar, booking quotation prices).');
$doc->addBullet('Rapid Prototyping: The procedural PHP approach allows quick implementation of features without framework overhead, supporting fast iteration cycles.');
$doc->addBullet('Continuous Improvement: Each development session built upon the previous one, fixing bugs, adding features, and refining the user experience based on testing feedback.');
$doc->addEmptyLine();

$doc->addParagraph('Development Phases:', true);
$doc->addTable(
    ['Phase', 'Week', 'Activities'],
    [
        ['Requirement Analysis & Design', 'Week 1-2', 'Define user roles and access levels, create ERD with 10 tables, design wireframes for 3 portals, setup project structure'],
        ['Foundation & Database Setup', 'Week 3-4', 'Create database schema, implement authentication (login, register, password reset, rate limiting), configure role-based access, build bootstrap architecture'],
        ['Core Module Development', 'Week 5-6', 'Build Event and Booking modules (CRUD, quotation/invoice workflow, availability calendar), Task module (assignment, FullCalendar integration), Expense module (submission, approval, receipt upload)'],
        ['E-Commerce & Portal UI', 'Week 7-8', 'Build Merchandise module (catalog, cart, checkout), Order management, implement responsive sidebar layout for all 3 portals, design system with Bootstrap Icons'],
        ['Notifications, Reports & Integration', 'Week 9-10', 'Implement real-time notification system, build Reports dashboard with analytics, add email notification templates, PDF invoice export, CSV data exports'],
        ['Security Hardening & Testing', 'Week 11', 'Fix SQL injection, XSS, CSRF vulnerabilities, enforce API authentication, input validation, system-wide audit and bug fixes'],
        ['Documentation & Deployment', 'Week 12', 'Comprehensive seed data, final testing, project documentation, deployment preparation'],
    ]
);

$doc->addHeading('3.1 System Design', 2);
$doc->addParagraph('System Architecture', true);
$doc->addParagraph('The Second Plan System follows a multi-portal web application architecture with shared configuration, role-based access control, RESTful JSON API endpoints, and server-rendered pages.');

$doc->addTable(
    ['Layer', 'Technology'],
    [
        ['Presentation Layer', 'HTML5, CSS3, JavaScript (Vanilla), Bootstrap Icons 1.11.3'],
        ['Application Layer', 'PHP 8.x (Procedural)'],
        ['Database Layer', 'MySQL 8.0 with PDO (PHP Data Objects)'],
        ['Calendar Integration', 'FullCalendar 6.1.9'],
        ['PDF Export', 'html2pdf.js v0.10.1'],
        ['Server Environment', 'Laragon (Apache + MySQL + PHP)'],
    ]
);

$doc->addParagraph('Application Flow:', true);
$doc->addParagraph('Every page in the system starts by loading config/bootstrap.php, which initializes the database connection, session management, and all helper functions. Authentication checks happen at the top of every protected page via require_login() and require_role(). Data entry occurs through HTML forms using fetch() API calls for AJAX operations and traditional POST for authentication pages. Server-side validation runs in PHP with helper functions such as sanitize(), isValidEmail(), and verifyCSRF(). Actions that affect other users trigger notifications via createNotification() and emails via the email system.');
$doc->addEmptyLine();

$doc->addParagraph('Directory Structure:', true);
$doc->addTable(
    ['Directory', 'Purpose'],
    [
        ['admin/', 'Admin panel (dashboard, CRUD pages, assets)'],
        ['api/', 'Shared JSON API endpoints (notifications, events, tasks, cart, orders)'],
        ['auth/', 'Authentication pages (login, register, logout, forgot/reset password)'],
        ['band/', 'Band member portal (tasks, expenses, events, schedule, profile)'],
        ['user/', 'Customer portal (dashboard, bookings, merchandise, cart, orders, profile)'],
        ['config/', 'Configuration and bootstrap files (.htaccess protected)'],
        ['includes/', 'Shared PHP functions, database, session, email (.htaccess protected)'],
        ['uploads/', 'User-uploaded files (posters, receipts, images)'],
        ['assets/', 'Shared static assets (images, notifications.js)'],
        ['logs/', 'Application error and email logs (.htaccess protected)'],
    ]
);

$doc->addHeading('3.2 Database Design', 2);
$doc->addParagraph('The database follows a relational model designed in MySQL 8.0, consisting of 12 tables and 3 reporting views. The Entity Relationship Diagram (ERD) illustrates the relationships between entities in the system.');
$doc->addEmptyLine();

$doc->addParagraph('Key Relationships:', true);
$doc->addBullet('users connects to roles through user_roles junction table (many-to-many relationship).');
$doc->addBullet('events links to users via created_by foreign key (one-to-many).');
$doc->addBullet('bookings links to users via user_id (nullable for guest bookings) and approved_by.');
$doc->addBullet('tasks links to users via assigned_to and assigned_by, and to events via event_id.');
$doc->addBullet('expenses links to users via submitted_by and approved_by, and to events via event_id.');
$doc->addBullet('orders links to users via user_id, and connects to merchandise through order_items junction table.');
$doc->addBullet('cart links to users and merchandise with a unique constraint per user-item pair.');
$doc->addBullet('notifications and activity_log link to users for tracking and audit purposes.');
$doc->addEmptyLine();
$doc->addParagraph('[Insert ERD Diagram Here]', true);

$doc->addHeading('3.3 Data Structure', 2);
$doc->addParagraph('The database design follows specific data requirements and constraints to ensure data integrity, security, and consistency:');
$doc->addEmptyLine();

$doc->addParagraph('Data Requirements:', true);
$doc->addBullet('All monetary values use DECIMAL(10,2) for precise financial calculations without floating-point errors.');
$doc->addBullet('Passwords are stored as bcrypt hashes (VARCHAR 255) using PHP password_hash() with PASSWORD_DEFAULT algorithm.');
$doc->addBullet('Reference numbers (quotation, invoice, order) use cryptographically random generation via bin2hex(random_bytes()) to prevent guessing.');
$doc->addBullet('File uploads are validated using MIME type detection (finfo) rather than file extension checking, with cryptographically random filenames.');
$doc->addBullet('ENUM types enforce valid states for status fields (booking status, payment status, task status, expense status, order status).');
$doc->addEmptyLine();

$doc->addParagraph('Data Constraints:', true);
$doc->addBullet('Primary keys use INT UNSIGNED AUTO_INCREMENT for all tables.');
$doc->addBullet('Foreign key constraints with appropriate ON DELETE behavior maintain referential integrity.');
$doc->addBullet('UNIQUE constraints on email (users), SKU (merchandise), order_number (orders), and key (settings) prevent duplicates.');
$doc->addBullet('Composite unique constraint on (user_id, merch_id) in the cart table prevents duplicate cart entries.');
$doc->addBullet('Nullable user_id in bookings allows guest bookings from the public landing page.');
$doc->addBullet('Timestamps (created_at, updated_at) with DEFAULT CURRENT_TIMESTAMP provide automatic audit trails.');

$doc->addPageBreak();

$doc->addHeading('CHAPTER 4: PROJECT OUTCOME / DESIGN AND IMPLEMENTATION', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addHeading('4.1 System Design', 2);
$doc->addParagraph('The Second Plan System was successfully implemented as a multi-portal web application with the following architecture and data flow:');
$doc->addEmptyLine();

$doc->addParagraph('System Architecture Implementation', true);
$doc->addParagraph('The system uses a procedural PHP architecture with a centralized bootstrap pattern. Every page loads config/bootstrap.php as its entry point, which chains the loading of configuration constants, database connection (PDO), session management, utility functions, authentication helpers, and email functions. This ensures all pages have consistent access to shared resources without manual dependency management.');
$doc->addEmptyLine();

$doc->addParagraph('Request Flow:', true);
$doc->addNumberedItem(1, 'Client sends HTTP request to a PHP page (e.g., admin/bookings.php).');
$doc->addNumberedItem(2, 'Bootstrap loads all dependencies and initializes session.');
$doc->addNumberedItem(3, 'Authentication check (require_login, require_role) validates user access.');
$doc->addNumberedItem(4, 'If request contains an API parameter (?api=list), the page handles it as a JSON API, processes the request, returns JSON, and exits.');
$doc->addNumberedItem(5, 'If no API parameter, the page renders the full HTML interface.');
$doc->addNumberedItem(6, 'Client-side JavaScript makes AJAX calls back to the same page or dedicated API endpoints for dynamic operations.');
$doc->addEmptyLine();

$doc->addParagraph('Module Implementation Summary:', true);

$doc->addParagraph('1. Event Management Module', true);
$doc->addBullet('Admin creates, updates, cancels, and deletes events with details (title, date, time, venue, location, capacity, price, poster image).');
$doc->addBullet('Events displayed on public landing page with live countdown timer to next upcoming event.');
$doc->addBullet('Band members view events on a shared FullCalendar 6.1.9 schedule with month, week, and list views.');
$doc->addEmptyLine();

$doc->addParagraph('2. Booking and Invoice Module', true);
$doc->addBullet('Customers submit booking requests through a multi-step wizard form with an interactive availability calendar showing booked and pending dates.');
$doc->addBullet('System generates a quotation number (QT-YYYYMMDD-XXXX) on submission using cryptographically random bytes.');
$doc->addBullet('Admin approves with price setting, generating an invoice number (INV-YYYYMMDD-XXXX) and setting a 14-day payment due date.');
$doc->addBullet('Customers view invoices with Maybank payment instructions and can upload payment receipts.');
$doc->addBullet('Admin marks bookings as paid with automated notification and email to customer.');
$doc->addBullet('Printable and PDF-exportable invoices using html2pdf.js.');
$doc->addEmptyLine();

$doc->addParagraph('3. Merchandise and E-Commerce Module', true);
$doc->addBullet('Admin manages product catalog with SKU, pricing, stock levels, categories (Apparel, Accessories, Music, Collectibles), and product images.');
$doc->addBullet('Low stock threshold alerts displayed on admin dashboard.');
$doc->addBullet('Customers browse products with search and category filtering, add to cart, and checkout.');
$doc->addBullet('Transactional checkout validates stock, creates orders, decrements inventory, and generates order numbers (SP-YYYYMMDD-XXXXXXXX) within a database transaction.');
$doc->addBullet('Admin manages order fulfillment with status tracking (Pending, Processing, Shipped, Delivered, Cancelled).');
$doc->addEmptyLine();

$doc->addParagraph('4. Task Management Module', true);
$doc->addBullet('Admin creates and assigns tasks to band members with priority levels (Low, Medium, High, Urgent) and due dates.');
$doc->addBullet('Band members view assigned tasks, update status (Todo, In Progress, Completed), and see tasks on FullCalendar color-coded by priority.');
$doc->addBullet('Automated notifications sent to band members on task assignment.');
$doc->addEmptyLine();

$doc->addParagraph('5. Expense Tracking Module', true);
$doc->addBullet('Band members submit expense claims with category (Equipment, Food, Marketing, Rental, Transport, Venue, Other), amount, vendor, description, and receipt upload.');
$doc->addBullet('Admin reviews, approves, or rejects expense claims with notification to submitter.');
$doc->addBullet('Receipt viewing supports both image (JPG/PNG) and PDF formats.');
$doc->addBullet('CSV export for expense reporting.');
$doc->addEmptyLine();

$doc->addParagraph('6. Notification and Email Module', true);
$doc->addBullet('Real-time notification system with bell icon dropdown on all pages across all three portals.');
$doc->addBullet('Shared IIFE JavaScript component (notifications.js) auto-enhances notification buttons on every page.');
$doc->addBullet('Polling-based updates every 30 seconds with mark-as-read and mark-all-read functionality.');
$doc->addBullet('HTML email templates for 7 notification types: password reset, booking submitted, booking approved, booking rejected, payment confirmed, order confirmed, task assigned.');
$doc->addEmptyLine();

$doc->addParagraph('7. Reports and Analytics Module', true);
$doc->addBullet('Admin reports dashboard with date range filtering (Today, This Week, This Month, Last Month, This Year, All Time, Custom).');
$doc->addBullet('Financial overview: booking revenue, merchandise sales, total expenses, net profit.');
$doc->addBullet('CSS-only charts: revenue vs expenses bar chart (last 12 months), booking status donut chart (conic-gradient).');
$doc->addBullet('Expenses by category breakdown, top selling merchandise, payment and merchandise summaries.');
$doc->addBullet('CSV export for all report data.');
$doc->addEmptyLine();

$doc->addParagraph('8. Authentication and Security Module', true);
$doc->addBullet('Login with rate limiting: 5 failed attempts trigger a 15-minute lockout period.');
$doc->addBullet('Password reset flow with secure tokens (random_bytes) and 1-hour expiry.');
$doc->addBullet('CSRF protection on all forms and API endpoints using session-stored and cookie-accessible tokens.');
$doc->addBullet('File upload validation using finfo MIME type detection with cryptographically random filenames.');
$doc->addBullet('Role-based access control enforced on all portal pages and API endpoints.');
$doc->addBullet('SQL injection prevention through parameterized PDO prepared statements.');
$doc->addBullet('XSS protection via htmlspecialchars() output escaping (e() helper function).');
$doc->addBullet('Activity logging for comprehensive audit trail (login, registration, settings changes, booking actions).');

$doc->addHeading('4.2 Database Design', 2);
$doc->addParagraph('The database was implemented in MySQL 8.0 with 12 tables and 3 reporting views. The following Entity Relationship Diagram and schema describe the implemented database structure:');
$doc->addEmptyLine();
$doc->addParagraph('[Insert ERD Diagram Here]', true);
$doc->addEmptyLine();

$doc->addParagraph('Database Tables Overview:', true);
$doc->addTable(
    ['Table', 'Purpose', 'Key Relationships'],
    [
        ['roles', 'Role definitions (admin, band_member, customer)', 'Linked via user_roles'],
        ['users', 'All user accounts with authentication data', 'Referenced by almost every table'],
        ['user_roles', 'Many-to-many user-role mapping', 'FK to users + roles'],
        ['events', 'Band gigs and performances', 'created_by -> users'],
        ['bookings', 'Customer booking requests and invoices', 'user_id -> users (nullable)'],
        ['tasks', 'To-do items assigned to band members', 'assigned_to, assigned_by -> users'],
        ['expenses', 'Band expense claims with receipts', 'submitted_by, approved_by -> users'],
        ['merchandise', 'Products for sale in the shop', 'Standalone catalog'],
        ['orders', 'Customer purchase orders', 'user_id -> users'],
        ['order_items', 'Line items within each order', 'order_id -> orders, merch_id -> merchandise'],
        ['cart', 'Shopping cart entries per user', 'user_id -> users, merch_id -> merchandise'],
        ['notifications', 'In-app notification queue', 'user_id -> users'],
        ['activity_log', 'Comprehensive audit trail', 'user_id -> users (nullable)'],
        ['settings', 'Key-value configuration store', 'Standalone'],
    ]
);

$doc->addParagraph('Database Views:', true);
$doc->addTable(
    ['View Name', 'Purpose'],
    [
        ['v_booking_summary', 'Monthly booking statistics by status with total revenue'],
        ['v_expense_summary', 'Monthly approved expense totals by category'],
        ['v_merchandise_inventory', 'Active merchandise with stock status (in_stock, low_stock, out_of_stock) and inventory value'],
    ]
);

$doc->addHeading('4.3 Data Dictionary', 2);
$doc->addParagraph('The following data dictionary provides a detailed listing of all tables, fields, data types, and their descriptions in the Second Plan database.');
$doc->addEmptyLine();

$doc->addParagraph('Table: roles', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['role_id', 'INT UNSIGNED AUTO_INCREMENT', 'PRIMARY KEY', 'Unique role identifier'],
        ['role_name', 'VARCHAR(50) UNIQUE', 'INDEX', 'Role name (admin, band_member, customer)'],
        ['description', 'TEXT', '-', 'Role description'],
        ['created_at', 'TIMESTAMP', '-', 'Creation timestamp'],
    ]
);

$doc->addParagraph('Table: users', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['user_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique user identifier'],
        ['email', 'VARCHAR(255) UNIQUE', 'INDEX', 'Login email address'],
        ['password_hash', 'VARCHAR(255)', '-', 'Bcrypt hashed password'],
        ['name', 'VARCHAR(255)', '-', 'Full name'],
        ['phone', 'VARCHAR(20)', '-', 'Contact number'],
        ['position', 'VARCHAR(100)', '-', 'Band member role/position'],
        ['profile_image', 'VARCHAR(255)', '-', 'Profile image file path'],
        ['status', 'ENUM(active,inactive,suspended)', 'INDEX', 'Account status'],
        ['email_verified', 'BOOLEAN', '-', 'Email verification flag'],
        ['verification_token', 'VARCHAR(64)', '-', 'Email verification token'],
        ['reset_token', 'VARCHAR(64)', '-', 'Password reset token'],
        ['reset_expires', 'DATETIME', '-', 'Reset token expiry time'],
        ['last_login', 'DATETIME', '-', 'Last successful login'],
        ['created_at', 'TIMESTAMP', '-', 'Account creation time'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update time'],
    ]
);

$doc->addParagraph('Table: user_roles', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['user_id', 'INT UNSIGNED', 'PK, FK', 'Reference to users table'],
        ['role_id', 'INT UNSIGNED', 'PK, FK', 'Reference to roles table'],
        ['assigned_at', 'TIMESTAMP', '-', 'Role assignment timestamp'],
    ]
);

$doc->addParagraph('Table: events', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['event_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique event identifier'],
        ['title', 'VARCHAR(255)', '-', 'Event name'],
        ['description', 'TEXT', '-', 'Event details'],
        ['date', 'DATE', 'INDEX', 'Event date'],
        ['start_time', 'TIME', '-', 'Starting time'],
        ['end_time', 'TIME', '-', 'Ending time'],
        ['venue', 'VARCHAR(255)', '-', 'Event venue name'],
        ['location', 'VARCHAR(255)', '-', 'Event address/location'],
        ['capacity', 'INT UNSIGNED', '-', 'Maximum attendee capacity'],
        ['seats_booked', 'INT UNSIGNED', '-', 'Number of seats booked'],
        ['price', 'DECIMAL(10,2)', '-', 'Event ticket price'],
        ['status', 'ENUM(scheduled,cancelled,completed,postponed)', 'INDEX', 'Event status'],
        ['poster_image', 'VARCHAR(255)', '-', 'Event poster file path'],
        ['created_by', 'INT UNSIGNED', 'FK', 'Admin who created the event'],
        ['created_at', 'TIMESTAMP', '-', 'Creation timestamp'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addParagraph('Table: bookings', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['booking_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique booking identifier'],
        ['user_id', 'INT UNSIGNED NULL', 'FK, INDEX', 'Customer (nullable for guests)'],
        ['event_id', 'INT UNSIGNED', 'FK', 'Related event reference'],
        ['company_name', 'VARCHAR(255)', '-', 'Company or organization name'],
        ['event_name', 'VARCHAR(255)', '-', 'Requested event title'],
        ['event_date', 'DATE', 'INDEX', 'Requested event date'],
        ['event_time', 'TIME', '-', 'Requested event time'],
        ['location', 'VARCHAR(255)', '-', 'Event location'],
        ['address', 'TEXT', '-', 'Full address'],
        ['postal_code', 'VARCHAR(20)', '-', 'Postal code'],
        ['city', 'VARCHAR(100)', '-', 'City'],
        ['state', 'VARCHAR(100)', '-', 'State'],
        ['price', 'DECIMAL(10,2)', '-', 'Approved price set by admin'],
        ['quotation_price', 'DECIMAL(10,2) NULL', '-', 'Budget proposed by customer'],
        ['status', 'ENUM(pending,approved,rejected,cancelled,completed)', 'INDEX', 'Booking status'],
        ['quotation_number', 'VARCHAR(50)', '-', 'Auto-generated QT reference'],
        ['invoice_number', 'VARCHAR(50)', '-', 'Auto-generated INV reference'],
        ['payment_status', 'ENUM(unpaid,paid)', '-', 'Payment status'],
        ['payment_due_date', 'DATE NULL', '-', 'Payment deadline'],
        ['paid_at', 'DATETIME NULL', '-', 'Payment confirmation time'],
        ['payment_receipt', 'VARCHAR(255) NULL', '-', 'Uploaded receipt file path'],
        ['approved_by', 'INT UNSIGNED', 'FK', 'Admin who approved/rejected'],
        ['created_at', 'TIMESTAMP', '-', 'Submission timestamp'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addParagraph('Table: tasks', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['task_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique task identifier'],
        ['title', 'VARCHAR(255)', '-', 'Task title'],
        ['description', 'TEXT', '-', 'Task details'],
        ['assigned_to', 'INT UNSIGNED', 'FK, INDEX', 'Band member assigned'],
        ['assigned_by', 'INT UNSIGNED', 'FK', 'Admin who assigned'],
        ['event_id', 'INT UNSIGNED', 'FK', 'Related event reference'],
        ['priority', 'ENUM(low,medium,high,urgent)', '-', 'Priority level'],
        ['status', 'ENUM(todo,in_progress,completed,cancelled)', 'INDEX', 'Task status'],
        ['due_date', 'DATE', 'INDEX', 'Task deadline date'],
        ['due_time', 'TIME', '-', 'Task deadline time'],
        ['completed_at', 'DATETIME', '-', 'Completion timestamp'],
        ['created_at', 'TIMESTAMP', '-', 'Creation timestamp'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addParagraph('Table: expenses', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['expense_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique expense identifier'],
        ['category', 'VARCHAR(100)', 'INDEX', 'Expense category'],
        ['amount', 'DECIMAL(10,2)', '-', 'Expense amount in MYR'],
        ['expense_date', 'DATE', 'INDEX', 'Date of expense'],
        ['vendor', 'VARCHAR(255)', '-', 'Vendor or payee name'],
        ['reference', 'VARCHAR(100)', '-', 'Reference number'],
        ['description', 'TEXT', '-', 'Expense description'],
        ['receipt', 'VARCHAR(255)', '-', 'Uploaded receipt file path'],
        ['status', 'ENUM(pending,approved,rejected,paid)', 'INDEX', 'Approval status'],
        ['submitted_by', 'INT UNSIGNED', 'FK', 'Band member who submitted'],
        ['approved_by', 'INT UNSIGNED', 'FK', 'Admin who approved/rejected'],
        ['event_id', 'INT UNSIGNED', 'FK', 'Related event reference'],
        ['created_at', 'TIMESTAMP', '-', 'Submission timestamp'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addParagraph('Table: merchandise', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['merch_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique item identifier'],
        ['name', 'VARCHAR(255)', '-', 'Product name'],
        ['sku', 'VARCHAR(100) UNIQUE', 'INDEX', 'Stock Keeping Unit code'],
        ['description', 'TEXT', '-', 'Product description'],
        ['price', 'DECIMAL(10,2)', '-', 'Selling price in MYR'],
        ['cost', 'DECIMAL(10,2)', '-', 'Cost price in MYR'],
        ['stock', 'INT UNSIGNED', '-', 'Current stock quantity'],
        ['low_stock_threshold', 'INT UNSIGNED', '-', 'Low stock alert threshold'],
        ['category', 'VARCHAR(100)', 'INDEX', 'Product category'],
        ['image', 'VARCHAR(255)', '-', 'Product image file path'],
        ['status', 'ENUM(active,inactive,discontinued)', 'INDEX', 'Product status'],
        ['created_at', 'TIMESTAMP', '-', 'Creation timestamp'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addParagraph('Table: orders', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['order_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique order identifier'],
        ['user_id', 'INT UNSIGNED', 'FK, INDEX', 'Customer who placed the order'],
        ['order_number', 'VARCHAR(50) UNIQUE', 'INDEX', 'Auto-generated order reference'],
        ['total_amount', 'DECIMAL(10,2)', '-', 'Total order amount in MYR'],
        ['status', 'ENUM(pending,processing,shipped,delivered,cancelled)', 'INDEX', 'Order status'],
        ['payment_status', 'ENUM(unpaid,paid,refunded)', '-', 'Payment status'],
        ['payment_method', 'VARCHAR(50)', '-', 'Payment method used'],
        ['shipping_address', 'TEXT', '-', 'Delivery address'],
        ['notes', 'TEXT', '-', 'Order notes'],
        ['created_at', 'TIMESTAMP', '-', 'Order placement timestamp'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addParagraph('Table: order_items', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['item_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique line item identifier'],
        ['order_id', 'INT UNSIGNED', 'FK', 'Reference to orders table'],
        ['merch_id', 'INT UNSIGNED', 'FK', 'Reference to merchandise table'],
        ['quantity', 'INT UNSIGNED', '-', 'Quantity ordered'],
        ['price', 'DECIMAL(10,2)', '-', 'Price per unit at purchase time'],
        ['subtotal', 'DECIMAL(10,2)', '-', 'Line item total'],
    ]
);

$doc->addParagraph('Table: cart', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['cart_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique cart entry identifier'],
        ['user_id', 'INT UNSIGNED', 'FK', 'Customer who owns the cart item'],
        ['merch_id', 'INT UNSIGNED', 'FK', 'Merchandise item in cart'],
        ['quantity', 'INT UNSIGNED', '-', 'Quantity in cart'],
        ['added_at', 'TIMESTAMP', '-', 'Timestamp when added to cart'],
    ]
);

$doc->addParagraph('Table: notifications', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['notification_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique notification identifier'],
        ['user_id', 'INT UNSIGNED', 'FK', 'Recipient user'],
        ['type', 'VARCHAR(50)', '-', 'Notification type'],
        ['title', 'VARCHAR(255)', '-', 'Notification title'],
        ['message', 'TEXT', '-', 'Notification message body'],
        ['link', 'VARCHAR(255)', '-', 'Target page URL'],
        ['is_read', 'BOOLEAN', 'INDEX', 'Read status flag'],
        ['created_at', 'TIMESTAMP', '-', 'Creation timestamp'],
    ]
);

$doc->addParagraph('Table: activity_log', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['log_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique log entry identifier'],
        ['user_id', 'INT UNSIGNED NULL', 'FK, INDEX', 'User who performed the action'],
        ['action', 'VARCHAR(100)', 'INDEX', 'Action type'],
        ['entity_type', 'VARCHAR(50)', '-', 'Type of entity affected'],
        ['entity_id', 'INT UNSIGNED', '-', 'ID of the affected entity'],
        ['details', 'TEXT', '-', 'JSON-formatted action details'],
        ['ip_address', 'VARCHAR(45)', '-', 'Client IP address'],
        ['user_agent', 'TEXT', '-', 'Client browser user agent'],
        ['created_at', 'TIMESTAMP', 'INDEX', 'Log entry timestamp'],
    ]
);

$doc->addParagraph('Table: settings', true);
$doc->addTable(
    ['Field Name', 'Type', 'Key', 'Description'],
    [
        ['setting_id', 'INT UNSIGNED AUTO_INCREMENT', 'PK', 'Unique setting identifier'],
        ['key', 'VARCHAR(100) UNIQUE', 'INDEX', 'Setting identifier key'],
        ['value', 'TEXT', '-', 'Setting value'],
        ['type', 'VARCHAR(50)', '-', 'Value type (string, boolean)'],
        ['description', 'TEXT', '-', 'Setting description'],
        ['updated_at', 'TIMESTAMP', '-', 'Last update timestamp'],
    ]
);

$doc->addPageBreak();

$doc->addHeading('CHAPTER 5: CONCLUSION', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addHeading('5.1 Significance and Contributions', 2);
$doc->addParagraph('The Second Plan System provides an effective digital solution for independent bands by transforming traditional manual and unorganized workflows into a structured, centralized management platform. The project makes several significant contributions:');
$doc->addEmptyLine();
$doc->addBullet('Operational Efficiency: The system automates event scheduling, booking approvals with quotation and invoice generation, and task assignment with notifications to band members. This minimizes errors, prevents double bookings through an availability calendar, and ensures better coordination among band members via a shared schedule calendar.');
$doc->addBullet('Financial Transparency: The implementation of a complete quotation-to-invoice payment workflow with automated due date tracking and payment confirmation notifications provides the band with clear financial visibility. The expense tracking system with receipt management and admin approval pipeline ensures accountability for all expenditures.');
$doc->addBullet('Customer Experience: The integrated booking system with availability calendar, merchandise e-commerce with shopping cart, and automated notification system create a professional customer-facing experience that was previously unavailable for independent bands in the Malaysian market.');
$doc->addBullet('Market Gap: As demonstrated in the benchmarking analysis, no existing Malaysian platform provides internal band management tools. The Second Plan System fills this gap by combining event management, booking workflows, task coordination, expense tracking, merchandise e-commerce, and role-based portals in a single platform.');
$doc->addBullet('Security Implementation: The system demonstrates proper web application security practices including CSRF protection, SQL injection prevention through parameterized queries, login rate limiting, secure file upload validation, XSS prevention, and comprehensive activity logging.');

$doc->addHeading('5.2 Limitations', 2);
$doc->addParagraph('Despite the comprehensive implementation, the system has several limitations that should be acknowledged:');
$doc->addEmptyLine();
$doc->addBullet('No Payment Gateway Integration: The system relies on manual bank transfer payments. There is no integration with payment gateways such as Stripe, Billplz, or FPX for automated online payments. Customers must upload payment receipts, and the admin must manually verify and confirm payments.');
$doc->addBullet('No Automated Tests: The system lacks unit tests, integration tests, and end-to-end tests. All testing was performed manually, which makes refactoring risky and does not guarantee regression-free development.');
$doc->addBullet('No Database Transactions for Multi-Step Operations: While the cart checkout uses database transactions, other multi-step operations (booking approval + notification + email) are not wrapped in transactions, risking inconsistent state if a step fails midway.');
$doc->addBullet('Email System Limitation: The production email system uses PHP mail() function, which is unreliable and often blocked by spam filters. A professional SMTP library such as PHPMailer would be more reliable.');
$doc->addBullet('No Pagination: All list pages currently load all records at once. For large datasets, this could cause performance issues and slow page load times.');
$doc->addBullet('Single Band Focus: The system is designed for a single band. It does not support multi-tenant architecture where multiple bands could each have their own instance.');
$doc->addBullet('No Email Verification: Registration does not require email verification, which means users can register with invalid email addresses.');

$doc->addHeading('5.3 Future Enhancement', 2);
$doc->addParagraph('The following enhancements are proposed for future development to address the identified limitations and extend the system capabilities:');
$doc->addEmptyLine();
$doc->addParagraph('High Priority:', true);
$doc->addBullet('Payment Gateway Integration: Integrate with Malaysian payment gateways (Billplz, FPX) or international providers (Stripe) for automated online payment processing, reducing manual verification workload.');
$doc->addBullet('Email Verification on Registration: Implement email verification flow to ensure valid email addresses and prevent fake accounts.');
$doc->addBullet('Pagination: Add server-side pagination with LIMIT/OFFSET to all list pages for improved performance with growing datasets.');
$doc->addEmptyLine();

$doc->addParagraph('Medium Priority:', true);
$doc->addBullet('Two-Factor Authentication (2FA): Add an optional second authentication factor for enhanced account security, particularly for admin accounts.');
$doc->addBullet('Image Optimization: Use PHP GD or Imagick to resize and compress uploaded images (posters, merchandise photos, receipts) to reduce storage and improve page load times.');
$doc->addBullet('SMTP Email Library: Replace PHP mail() with PHPMailer or similar library for reliable email delivery in production.');
$doc->addBullet('Remember Me Cookie: Implement persistent login cookies for returning users.');
$doc->addEmptyLine();

$doc->addParagraph('Low Priority:', true);
$doc->addBullet('Multi-Language Support: Prepare for internationalization by extracting UI strings into language files, starting with English and Malay (Bahasa Malaysia).');
$doc->addBullet('CSRF Token Rotation: Implement per-request CSRF token rotation for enhanced security.');
$doc->addBullet('Multi-Band Support: Redesign the database architecture to support multi-tenant usage where multiple bands can use the system independently.');
$doc->addBullet('Mobile Application: Develop companion mobile applications (iOS/Android) using the existing API endpoints for on-the-go access.');

$doc->addPageBreak();

$doc->addHeading('REFERENCES', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addParagraph('GoLive Malaysia. (2024). Event Management Platform. Retrieved from https://www.golive.com.my');
$doc->addEmptyLine();
$doc->addParagraph('Gigsmore. (2024). Artist and Event Organizer Platform. Retrieved from https://www.gigsmore.com');
$doc->addEmptyLine();
$doc->addParagraph('LOLAsia. (2024). Entertainment Ticketing and Event Promotion. Retrieved from https://www.lolasia.com');
$doc->addEmptyLine();
$doc->addParagraph('PHP Documentation. (2024). PHP: Hypertext Preprocessor Manual. Retrieved from https://www.php.net/docs.php');
$doc->addEmptyLine();
$doc->addParagraph('MySQL Documentation. (2024). MySQL 8.0 Reference Manual. Retrieved from https://dev.mysql.com/doc/refman/8.0/en/');
$doc->addEmptyLine();
$doc->addParagraph('FullCalendar. (2024). FullCalendar v6 Documentation. Retrieved from https://fullcalendar.io/docs');
$doc->addEmptyLine();
$doc->addParagraph('Bootstrap Icons. (2024). Bootstrap Icons v1.11. Retrieved from https://icons.getbootstrap.com/');
$doc->addEmptyLine();
$doc->addParagraph('OWASP Foundation. (2023). OWASP Top Ten Web Application Security Risks. Retrieved from https://owasp.org/www-project-top-ten/');
$doc->addEmptyLine();
$doc->addParagraph('html2pdf.js. (2024). Client-side HTML-to-PDF rendering. Retrieved from https://ekoopmans.github.io/html2pdf.js/');
$doc->addEmptyLine();
$doc->addParagraph('Connolly, T., & Begg, C. (2015). Database Systems: A Practical Approach to Design, Implementation, and Management (6th ed.). Pearson.');
$doc->addEmptyLine();
$doc->addParagraph('Sommerville, I. (2016). Software Engineering (10th ed.). Pearson.');

$doc->addPageBreak();

$doc->addHeading('APPENDICES', 1);
$doc->addHorizontalLine();
$doc->addEmptyLine();

$doc->addHeading('Appendix A: System Screenshots', 2);
$doc->addParagraph('[Insert screenshots of the following pages:]');
$doc->addBullet('Public Landing Page (index.php)');
$doc->addBullet('Login Page');
$doc->addBullet('Admin Dashboard');
$doc->addBullet('Admin Bookings Management');
$doc->addBullet('Admin Events Management');
$doc->addBullet('Admin Merchandise Management');
$doc->addBullet('Admin Reports Dashboard');
$doc->addBullet('Band Member Dashboard with FullCalendar');
$doc->addBullet('Band Member Task Management');
$doc->addBullet('Customer Dashboard');
$doc->addBullet('Customer Booking Form with Availability Calendar');
$doc->addBullet('Customer Merchandise Shop');
$doc->addBullet('Customer Invoice Page');
$doc->addBullet('Notification Dropdown');
$doc->addEmptyLine();

$doc->addHeading('Appendix B: Database Schema (SQL)', 2);
$doc->addParagraph('[Insert contents of config/schema/schema.sql]');
$doc->addEmptyLine();

$doc->addHeading('Appendix C: Seed Data (SQL)', 2);
$doc->addParagraph('[Insert contents of config/schema/seed_data.sql]');
$doc->addEmptyLine();

$doc->addHeading('Appendix D: Test Accounts', 2);
$doc->addTable(
    ['Role', 'Email', 'Password', 'Name'],
    [
        ['Admin', 'admin@secondplan.com', 'Admin@123', 'Admin User'],
        ['Band Member', 'ameer@secondplan.com', 'Admin@123', 'Ameer (Vocalist)'],
        ['Band Member', 'zimi@secondplan.com', 'Admin@123', 'Zimi (Guitarist)'],
        ['Band Member', 'fairuz@secondplan.com', 'Admin@123', 'Fairuz (Bassist)'],
        ['Band Member', 'one@secondplan.com', 'Admin@123', 'One (Drummer)'],
        ['Customer', 'sarah@email.com', 'Admin@123', 'Sarah'],
        ['Customer', 'michael@email.com', 'Admin@123', 'Michael'],
        ['Customer', 'aina@email.com', 'Admin@123', 'Aina'],
    ]
);

$outputFile = __DIR__ . '/SecondPlan_Technical_Report.docx';
$doc->save($outputFile);
echo "Report generated: $outputFile\n";
echo "File size: " . number_format(filesize($outputFile)) . " bytes\n";
