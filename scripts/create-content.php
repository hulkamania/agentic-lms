<?php
/**
 * Create the Agentic LMS Learning Path course with comprehensive content.
 */

define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/mod/label/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

$admin = get_admin();
$USER = $admin;

// ------------------------------------------------------------------
// 1. Create or update the Learning Path course
// ------------------------------------------------------------------
$course_shortname = 'LMSPM2024';
$course_fullname  = 'LMS Product Management & Market Intelligence';

echo "Looking for existing course '$course_shortname'...\n";
$existing = $DB->get_record('course', ['shortname' => $course_shortname]);

if ($existing) {
    $course = get_course($existing->id);
    echo "Found existing course id=$course->id. Updating...\n";
} else {
    $course = new stdClass();
    $course->shortname         = $course_shortname;
    $course->fullname          = $course_fullname;
    $course->category         = 1; // Misc category
    $course->summary          = 'A comprehensive learning path for Product Managers evaluating a new LMS SaaS product in the Swiss market, leveraging agentic autonomous software factories.';
    $course->summaryformat    = FORMAT_HTML;
    $course->format           = 'topics';
    $course->numsections      = 6;
    $course->startdate        = time();
    $course->enddate          = 0;
    $course->visible          = 1;
    $course->enablecompletion = 1;
    $course->lang             = 'en';

    $course = create_course($course);
    echo "Created course id=$course->id.\n";
}

// Enrol admin as editingteacher
$context = context_course::instance($course->id);
$editingteacher = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($editingteacher) {
    role_assign($editingteacher->id, $admin->id, $context->id);
}

// ------------------------------------------------------------------
// Helper: add a Page resource to a section
// ------------------------------------------------------------------
function add_page_to_section($course, $sectionnum, $title, $content) {
    global $DB, $USER;

    $module = $DB->get_record('modules', ['name' => 'page'], '*', MUST_EXIST);

    $page = new stdClass();
    $page->course        = $course->id;
    $page->name          = $title;
    $page->intro         = '';
    $page->introformat   = FORMAT_HTML;
    $page->content       = $content;
    $page->contentformat = FORMAT_HTML;
    $page->display       = 0;
    $page->legacyfiles   = 0;
    $page->revision      = 1;
    $page->timemodified  = time();

    $cm = new stdClass();
    $cm->course           = $course->id;
    $cm->module           = $module->id;
    $cm->instance         = 0; // set later
    $cm->section          = $sectionnum;
    $cm->visible          = 1;
    $cm->visibleold       = 1;
    $cm->groupmode        = 0;
    $cm->groupingid       = 0;
    $cm->completion       = 1;
    $cm->completiongradeitemnumber = null;
    $cm->completionpassgrade = 0;
    $cm->completionview   = 1;
    $cm->completionexpected = 0;
    $cm->showdescription  = 0;

    $page->id = page_add_instance($page, $cm);
    if (!$page->id) {
        echo "  ERROR creating page '$title'\n";
        return false;
    }

    $cm->instance = $page->id;
    $cm->id = add_course_module($cm);
    course_add_cm_to_section($course->id, $cm->id, $sectionnum);

    rebuild_course_cache($course->id, true);
    echo "  Added page: $title (section $sectionnum)\n";
    return true;
}

// ------------------------------------------------------------------
// Helper: add a Label to a section (for rich section intros)
// ------------------------------------------------------------------
function add_label_to_section($course, $sectionnum, $content) {
    global $DB;

    $module = $DB->get_record('modules', ['name' => 'label'], '*', MUST_EXIST);

    $label = new stdClass();
    $label->course      = $course->id;
    $label->name        = 'Overview';
    $label->intro       = $content;
    $label->introformat = FORMAT_HTML;
    $label->timemodified = time();

    $cm = new stdClass();
    $cm->course   = $course->id;
    $cm->module   = $module->id;
    $cm->section  = $sectionnum;
    $cm->visible  = 1;

    $label->id = label_add_instance($label, $cm);
    if (!$label->id) {
        echo "  ERROR creating label\n";
        return false;
    }

    $cm->instance = $label->id;
    $cm->id = add_course_module($cm);
    course_add_cm_to_section($course->id, $cm->id, $sectionnum);

    rebuild_course_cache($course->id, true);
    echo "  Added label to section $sectionnum\n";
    return true;
}

// ------------------------------------------------------------------
// 2. Define Section Content
// ------------------------------------------------------------------

$sections = [
    0 => [
        'title' => 'Course Overview',
        'label' => '<div class="alert alert-info"><h4>Welcome</h4><p>This course is designed for Product Managers evaluating the launch of a new <strong>LMS SaaS product</strong> in Switzerland. It covers market intelligence, technical architecture, competitive landscape, and cost modeling using <strong>agentic autonomous software factories</strong>.</p></div>',
    ],
    1 => [
        'title' => 'Module 1: LMS Fundamentals',
        'label' => '<p><strong>Objective:</strong> Understand what an LMS is, its core features, and the value chain.</p>',
        'pages' => [
            [
                'title' => 'What is an LMS?',
                'content' => '<h3>Definition</h3>
<p>A <strong>Learning Management System (LMS)</strong> is a software application for the administration, documentation, tracking, reporting, automation, and delivery of educational courses, training programs, or learning and development programs.</p>
<h3>Core Components</h3>
<ul>
<li><strong>Content Management (LCMS):</strong> Authoring, storage, versioning, and delivery of learning content.</li>
<li><strong>User Management:</strong> Enrolment, roles, groups, and permissions.</li>
<li><strong>Assessment Engine:</strong> Quizzes, assignments, rubrics, and proctoring.</li>
<li><strong>Tracking & Reporting:</strong> xAPI / SCORM compliance, progress dashboards, analytics.</li>
<li><strong>Communication:</strong> Forums, messaging, notifications, virtual classrooms.</li>
<li><strong>Certification:</strong> Badges, certificates, competency mapping.</li>
</ul>
<h3>Deployment Models</h3>
<ul>
<li><strong>Self-hosted (On-Premise):</strong> Full control, high TCO (Moodle, Canvas).</li>
<li><strong>Cloud-hosted SaaS:</strong> Vendor-managed, subscription pricing (TalentLMS, Litmos, Docebo).</li>
<li><strong>PaaS / Headless:</strong> API-first, composable architecture for embedded learning.</li>
</ul>',
            ],
            [
                'title' => 'The LMS Value Chain',
                'content' => '<h3>Value Chain Analysis</h3>
<p>An LMS sits at the intersection of several value streams:</p>
<ol>
<li><strong>Content Producers:</strong> Instructional designers, subject-matter experts, video producers.</li>
<li><strong>Learning Architects:</strong> Curriculum designers, competency mappers.</li>
<li><strong>IT Operations:</strong> Hosting, integrations, security, compliance.</li>
<li><strong>Learners:</strong> Employees, students, customers.</li>
<li><strong>Administrators:</strong> HR, L&D, academic registrars.</li>
<li><strong>Analytics Consumers:</strong> Executives, regulators, accreditation bodies.</li>
</ol>
<h3>Key Metrics</h3>
<ul>
<li>Learner engagement rate</li>
<li>Course completion rate</li>
<li>Time-to-competency</li>
<li>ROI on training spend</li>
<li>Net Promoter Score (NPS) for learning</li>
</ul>',
            ],
        ],
    ],
    2 => [
        'title' => 'Module 2: Technical Architecture for LMS SaaS',
        'label' => '<p><strong>Objective:</strong> Map the modern technical architecture required to build and operate a scalable LMS SaaS.</p>',
        'pages' => [
            [
                'title' => 'High-Level SaaS Architecture',
                'content' => '<h3>Multi-Tenant SaaS Reference Architecture</h3>
<pre style="background:#f5f5f5;padding:10px;border-radius:4px;">
┌─────────────────────────────────────────┐
│           CDN / Edge (CloudFront)       │
├─────────────────────────────────────────┤
│   API Gateway / WAF / Load Balancer     │
├─────────────────────────────────────────┤
│  Microservices (K8s / ECS / Cloud Run)  │
│  - Auth Service (OAuth2 / SAML / OIDC) │
│  - Content Service (headless CMS)       │
│  - Assessment Service                   │
│  - Notification Service                 │
│  - Analytics / xAPI LRS                 │
│  - Enrollment & Billing Service         │
├─────────────────────────────────────────┤
│  Event Bus (Kafka / RabbitMQ / SQS)     │
├─────────────────────────────────────────┤
│  Data Layer                             │
│  - Relational DB (PostgreSQL / Aurora)  │
│  - Object Store (S3) for content        │
│  - Search (Elasticsearch / OpenSearch)  │
│  - Cache (Redis / ElastiCache)          │
│  - Data Lake (S3 + Athena / Snowflake)  │
└─────────────────────────────────────────┘
</pre>
<h3>Scalability Patterns</h3>
<ul>
<li><strong>Database:</strong> Read replicas, connection pooling (PgBouncer), sharding by tenant.</li>
<li><strong>Media:</strong> Adaptive bitrate streaming (HLS/DASH), transcoding pipelines (AWS MediaConvert).</li>
<li><strong>Search:</strong> Full-text index of courses, users, and content with faceted search.</li>
<li><strong>Real-time:</strong> WebSockets for live sessions, notifications via SSE or push.</li>
</ul>',
            ],
            [
                'title' => 'Core Standards & Interoperability',
                'content' => '<h3>eLearning Standards</h3>
<ul>
<li><strong>SCORM 1.2 / 2004:</strong> Legacy packaging; still required by enterprise buyers.</li>
<li><strong>xAPI (Tin Can):</strong> Modern statement-based tracking; decentralized Learning Record Store (LRS).</li>
<li><strong>LTI 1.3 / Advantage:</strong> Deep linking, names and roles provisioning, assignment and grade services.</li>
<li><strong>Common Cartridge (IMS):</strong> Content portability between LMS platforms.</li>
<li><strong>QTI 2.1:</strong> Standardized assessment item exchange.</li>
</ul>
<h3>Integration Ecosystem</h3>
<ul>
<li><strong>HRIS:</strong> Workday, SAP SuccessFactors, BambooHR (for user provisioning).</li>
<li><strong>Identity:</strong> Azure AD, Okta, Auth0, Keycloak.</li>
<li><strong>CRM:</strong> Salesforce, HubSpot (for customer education LMS).</li>
<li><strong>Video:</strong> Zoom, MS Teams, BigBlueButton, Jitsi.</li>
<li><strong>BI:</strong> Power BI, Tableau, Metabase.</li>
</ul>',
            ],
            [
                'title' => 'Security & Compliance',
                'content' => '<h3>Security Architecture</h3>
<ul>
<li><strong>Encryption at Rest:</strong> AES-256 for DB and object storage.</li>
<li><strong>Encryption in Transit:</strong> TLS 1.3 minimum, HSTS enabled.</li>
<li><strong>IAM:</strong> RBAC + ABAC; least-privilege service accounts.</li>
<li><strong>Secrets Management:</strong> AWS Secrets Manager, HashiCorp Vault, or Kubernetes sealed secrets.</li>
<li><strong>Network:</strong> VPC isolation, private subnets for DB, security groups, WAF rules.</li>
</ul>
<h3>Compliance Requirements</h3>
<ul>
<li><strong>GDPR (EU):</strong> Data portability, right to erasure, DPA, SCCs.</li>
<li><strong>FADP (Switzerland):</strong> Swiss data protection law; stricter than GDPR in some areas.</li>
<li><strong>ISO 27001:</strong> Information security management.</li>
<li><strong>ISO 27701:</strong> Privacy information management.</li>
<li><strong>SOC 2 Type II:</strong> Required by US enterprise customers.</li>
<li><strong>Accessibility:</strong> WCAG 2.1 AA (EN 301 549 in Europe).</li>
<li><strong>Cloud Act & Schrems II:</strong> Swiss federal and cantonal buyers often require Swiss-resident data hosting.</li>
</ul>',
            ],
        ],
    ],
    3 => [
        'title' => 'Module 3: The Swiss LMS Market',
        'label' => '<p><strong>Objective:</strong> Understand the size, structure, regulations, and buying behavior of the Swiss LMS market.</p>',
        'pages' => [
            [
                'title' => 'Market Size & Structure',
                'content' => '<h3>Market Overview</h3>
<p>Switzerland has a <strong>fragmented but high-value LMS market</strong> driven by its unique federal structure and strong education sector.</p>
<ul>
<li><strong>Education Market:</strong> ~250,000 university students, strong vocational education (VPET) system with 2/3 of youth in dual-track apprenticeships.</li>
<li><strong>Corporate Market:</strong> High density of pharma (Roche, Novartis), banking (UBS, Credit Suisse), and precision manufacturing. Strong compliance training culture.</li>
<li><strong>Public Sector:</strong> 26 cantons each with independent education ministries; federal administration (BIT / Swisstopo / SBB).</li>
</ul>
<h3>Key Market Drivers</h3>
<ol>
<li><strong>Digitalization Mandates:</strong> "Digital Switzerland" strategy; cantonal digital school programs.</li>
<li><strong>Compliance Training:</strong> FINMA, Swissmedic, FDFA mandatory training requirements.</li>
<li><strong>Multilingualism:</strong> German, French, Italian, Romansh — content and UI must support all national languages.</li>
<li><strong>Data Sovereignty:</strong> Federal and cantonal IT strategies increasingly require data residency in Switzerland.</li>
</ol>
<h3>Estimates</h3>
<ul>
<li>Swiss EdTech market: ~CHF 500M–800M annually (including corporate L&D platforms).</li>
<li>LMS-specific addressable market: ~CHF 100M–150M/year in software licenses and services.</li>
<li>Average deal size: CHF 20k–200k/year for mid-market; CHF 500k+ for enterprise.</li>
</ul>',
            ],
            [
                'title' => 'Regulatory & Procurement Landscape',
                'content' => '<h3>Federal Procurement</h3>
<ul>
<li><strong>CH-LOGIN:</strong> Federal e-ID and single sign-on infrastructure.</li>
<li><strong>eGovernment Switzerland:</strong> Standardized procurement frameworks; IT projects > CHF 50k require tendering.</li>
<li><strong>BKB / Bundeskasse:</strong> Federal treasury payment integration.</li>
<li><strong>DDPS / BIT:</strong> Defense and federal IT security standards.</li>
</ul>
<h3>Cantonal Procurement</h3>
<p>Each canton runs its own IT procurement:</p>
<ul>
<li><strong>Public tenders:</strong> Published on Simap.ch; typically 4–12 month cycles.</li>
<li><strong>Framework agreements:</strong> Some cantons (ZH, BE, GE) have central IT purchasing bodies.</li>
<li><strong>Data residency clauses:</strong> Increasingly common in RFPs since Schrems II ruling.</li>
</ul>
<h3>Education-Specific Regulations</h3>
<ul>
<li><strong>EDK (Swiss Conference of Cantonal Ministers of Education):</strong> Sets curriculum frameworks but does not standardize LMS.</li>
<li><strong>SEFRI (State Secretariat for Education, Research and Innovation):</strong> Funds VPET digitalization.</li>
<li><strong>Swissuniversities:</strong> Coordinates higher-ed IT; runs SWITCH (national NREN).</li>
<li><strong>SWITCH:</strong> Provides identity federation (SWITCHaai / eduID) used by 95% of universities.</li>
</ul>',
            ],
        ],
    ],
    4 => [
        'title' => 'Module 4: Top Players in the Swiss LMS Market',
        'label' => '<p><strong>Objective:</strong> Identify and analyze the dominant LMS vendors serving the Swiss market.</p>',
        'pages' => [
            [
                'title' => 'Global Leaders in Switzerland',
                'content' => '<h3>1. Moodle (Open Source / Moodle Workplace)</h3>
<ul>
<li><strong>Presence:</strong> Dominant in Swiss higher education (ETH Zürich, EPFL, UZH, UNIL, UniBasel all use Moodle).</li>
<li><strong>Swiss Partner:</strong> Moodle.ch (Educa.ch network); multiple local Moodle partners (Unowhy, Nectunt).</li>
<li><strong>Strengths:</strong> Cost, community, SCORM/xAPI, vast plugin ecosystem, SWITCHaai integration.</li>
<li><strong>Weaknesses:</strong> UX dated without theme investment; requires hosting expertise; mobile app limited.</li>
</ul>
<h3>2. Canvas by Instructure</h3>
<ul>
<li><strong>Presence:</strong> Growing in Swiss universities (FHNW, some FHNW departments).</li>
<li><strong>Swiss Hosting:</strong> No Swiss data center; hosted in EU (Frankfurt/Amsterdam).</li>
<li><strong>Strengths:</strong> Modern UX, strong mobile, LTI 1.3 Advantage, native video, analytics.</li>
<li><strong>Weaknesses:</strong> Price (per-student model); data sovereignty concerns for federal clients; limited Swiss language support.</li>
</ul>
<h3>3. Blackboard / Anthology</h3>
<ul>
<li><strong>Presence:</strong> Declining in Switzerland; some former users migrated to Moodle or Canvas.</li>
<li><strong>Strengths:</strong> Deep feature set, Ally accessibility, large enterprise integrations.</li>
<li><strong>Weaknesses:</strong> Reputation for poor UX, high cost, declining market share globally.</li>
</ul>
<h3>4. SAP SuccessFactors Learning</h3>
<ul>
<li><strong>Presence:</strong> Strong in large Swiss enterprises (Nestlé, Novartis, Roche historically).</li>
<li><strong>Strengths:</strong> Deep ERP integration, compliance management, multi-language, global scale.</li>
<li><strong>Weaknesses:</strong> Expensive, complex implementation, poor UX for content authors, not suitable for academic use.</li>
</ul>',
            ],
            [
                'title' => 'Regional & Niche Players',
                'content' => '<h3>5. OLAT (Open Source)</h3>
<ul>
<li><strong>Origin:</strong> University of Zurich (UZH); widely used in Swiss universities and some vocational schools.</li>
<li><strong>Strengths:</strong> Swiss-developed, strong test engine, SWITCHaai, data sovereignty.</li>
<li><strong>Weaknesses:</strong> Smaller community than Moodle; limited corporate L&D features; UI aging.</li>
</ul>
<h3>6. ILIAS (Open Source)</h3>
<ul>
<li><strong>Origin:</strong> German; used by some Swiss vocational colleges and SMEs.</li>
<li><strong>Strengths:</strong> SCORM/xAPI, certification workflows, EU/German compliance focused.</li>
<li><strong>Weaknesses:</strong> German-centric; limited Swiss market penetration; UI not modern.</li>
</ul>
<h3>7. Swiss-specific SaaS / EdTech</h3>
<ul>
<li><strong>Skoove / flowkey:</strong> Not LMS but show Swiss EdTech capability in niche verticals.</li>
<li><strong>Educa.ch:</strong> Umbrella organization promoting EdTech in Switzerland; not an LMS vendor but a channel.</li>
<li><strong>Learnify / Scook (CH):</strong> Small K-12 focused platforms with limited scale.</li>
</ul>
<h3>8. Corporate-Focused SaaS</h3>
<ul>
<li><strong>Docebo:</strong> Italian-Canadian; growing Swiss enterprise presence (Swiss Re, some banks).</li>
<li><strong>360Learning:</strong> French; collaborative learning platform; used by some Swiss SMEs and startups.</li>
<li><strong>TalentLMS:</strong> Greek; low-cost SMB option; limited Swiss localization.</li>
<li><strong>Litmos (SAP):</strong> Corporate training; competes with SuccessFactors in mid-market.</li>
</ul>',
            ],
            [
                'title' => 'Competitive Gap Analysis',
                'content' => '<h3>Identified Market Gaps</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;">
<tr style="background:#eee;"><th>Gap</th><th>Current State</th><th>Opportunity</th></tr>
<tr>
<td><strong>Swiss-hosted SaaS</strong></td>
<td>Most global vendors host in EU/US. No major vendor guarantees Swiss-only data.</td>
<td>FADP-compliant, Swiss data-center SaaS with SWITCHaai federation.</td>
</tr>
<tr>
<td><strong>VPET / Apprenticeship</strong></td>
<td>Moodle and OLAT dominate; neither has strong workplace competency tracking.</td>
<td>LMS built for dual-track apprenticeship: employer + school dashboard, e-portfolio, competency grids.</td>
</tr>
<tr>
<td><strong>K-12 Cantonal Unified</strong></td>
<td>Each canton uses different tools; no unified K-12 LMS in Switzerland.</td>
<td>Multi-tenant SaaS for cantonal education departments with per-canton curriculum configuration.</td>
</tr>
<tr>
<td><strong>AI-Powered Personalization</strong></td>
<td>Basic adaptive learning rare in Swiss market.</td>
<td>Agentic AI tutors, automated content generation, competency gap analysis.</td>
</tr>
<tr>
<td><strong>Content Marketplace</strong></td>
<td>No Swiss-centric e-learning content marketplace exists.</td>
<td>Integrated marketplace for Swiss-compliant training (FINMA, Swissmedic, data protection).</td>
</tr>
</table>
<h3>Switching Costs</h3>
<ul>
<li>Academic: High (content migration, faculty training, integrations). Typical cycle: 5–10 years.</li>
<li>Corporate: Medium-High (ERP integrations, compliance audit trails). Typical cycle: 3–5 years.</li>
<li>K-12: Medium (teacher training, parental access). Typical cycle: 3–7 years.</li>
</ul>',
            ],
        ],
    ],
    5 => [
        'title' => 'Module 5: Product Strategy & Cost Modeling',
        'label' => '<p><strong>Objective:</strong> Build a business case and cost model for a new Swiss LMS SaaS using agentic autonomous software factories.</p>',
        'pages' => [
            [
                'title' => 'Product Positioning',
                'content' => '<h3>Recommended Positioning: "Swiss Agentic LMS"</h3>
<p>A Switzerland-first, AI-native LMS SaaS targeting:</p>
<ol>
<li><strong>Primary:</strong> Swiss vocational schools (VPET) and SMEs needing FADP compliance.</li>
<li><strong>Secondary:</strong> Cantonal K-12 education departments seeking unified platforms.</li>
<li><strong>Tertiary:</strong> Swiss enterprises (pharma, banking) with strict data residency and compliance needs.</li>
</ol>
<h3>Unique Value Proposition</h3>
<ul>
<li><strong>Data Sovereignty:</strong> Swiss-hosted, FADP-native, no data leaves Switzerland.</li>
<li><strong>Agentic AI:</strong> Autonomous course generation, intelligent tutoring, automated assessment feedback, real-time competency mapping.</li>
<li><strong>Swiss Integration:</strong> Native SWITCHaai / eduID, CH-LOGIN, Simap procurement APIs, cantonal curriculum APIs.</li>
<li><strong>Multi-lingual by Design:</strong> All four national languages + English; AI-assisted content localization.</li>
</ul>
<h3>Monetization Model</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;">
<tr style="background:#eee;"><th>Segment</th><th>Model</th><th>Price Point</th></tr>
<tr><td>VPET / Schools</td><td>Per-student / year</td><td>CHF 5–15 / student / year</td></tr>
<tr><td>SME Corporate</td><td>Per-seat / year</td><td>CHF 50–150 / seat / year</td></tr>
<tr><td>Enterprise</td><td>Platform fee + usage</td><td>CHF 30k–150k / year base</td></tr>
<tr><td>Content Marketplace</td><td>Revenue share</td><td>20–30% of content sales</td></tr>
</table>',
            ],
            [
                'title' => 'Agentic Autonomous Software Factory Model',
                'content' => '<h3>What is an Agentic Software Factory?</h3>
<p>An <strong>agentic autonomous software factory</strong> uses LLM-based coding agents (e.g., GitHub Copilot Workspace, Devin, Amazon Q, or open-source frameworks like SWE-agent, OpenDevin) to autonomously generate, test, and deploy software. For an LMS SaaS, this changes the cost structure dramatically.</p>
<h3>Architecture for Agentic Development</h3>
<pre style="background:#f5f5f5;padding:10px;border-radius:4px;">
┌─────────────────────────────────────────┐
│  Product Manager (Human)                  │
│  → Writes PRDs, acceptance criteria      │
├─────────────────────────────────────────┤
│  Agent Orchestrator (e.g., LangChain)     │
│  → Decomposes tasks, assigns agents      │
├─────────────────────────────────────────┤
│  Specialized Agents:                      │
│  - UI/UX Agent (React, Tailwind)        │
│  - Backend Agent (Node.js / Go / Rust)   │
│  - Data Agent (PostgreSQL schema, ETL)  │
│  - Test Agent (Playwright, Jest, k6)     │
│  - DevOps Agent (Terraform, K8s YAML)    │
│  - Security Agent (SAST/DAST pipelines)  │
├─────────────────────────────────────────┤
│  CI/CD + Human-in-the-Loop Review       │
│  → Auto-merge on green + human approval  │
├─────────────────────────────────────────┤
│  Auto-Deployed to Staging / Prod        │
└─────────────────────────────────────────┘
</pre>
<h3>Estimated Cost Structure (Agentic vs Traditional)</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;">
<tr style="background:#eee;"><th>Phase</th><th>Traditional (CHF)</th><th>Agentic Factory (CHF)</th></tr>
<tr><td>MVP (0–6 months)</td><td>500k–1.2M</td><td>100k–250k</td></tr>
<tr><td>V1 Product (6–18 months)</td><td>1.5M–3M</td><td>300k–600k</td></tr>
<tr><td>Team Size (FTE)</td><td>8–15 engineers</td><td>2–4 engineers + 10 agent instances</td></tr>
<tr><td>Annual Run Rate (Ops)</td><td>1M–2M</td><td>200k–400k</td></tr>
<tr><td>Content Gen (courses)</td><td>CHF 10k / course (manual)</td><td>CHF 500 / course (AI-generated + human review)</td></tr>
</table>
<h3>Risks of Agentic Approach</h3>
<ul>
<li><strong>Code Quality:</strong> Agents produce working but not always maintainable code; requires strong architecture guardrails.</li>
<li><strong>Security:</strong> Agents may not understand Swiss compliance nuances; human security review mandatory.</li>
<li><strong>Context Limits:</strong> Large LMS codebase may exceed agent context windows; needs modular microservices.</li>
<li><strong>Technical Debt:</strong> Rapid generation without refactoring accumulates debt; schedule 20% of agent cycles for refactoring.</li>
</ul>',
            ],
            [
                'title' => 'Financial Model & Go-to-Market',
                'content' => '<h3>5-Year Financial Projection (Conservative)</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;">
<tr style="background:#eee;"><th>Year</th><th>Revenue</th><th>OpEx</th><th>EBITDA</th><th>Customers</th></tr>
<tr><td>1</td><td>CHF 0</td><td>CHF 250k</td><td>-250k</td><td>0 (MVP dev)</td></tr>
<tr><td>2</td><td>CHF 150k</td><td>CHF 400k</td><td>-250k</td><td>3 pilot (VPET)</td></tr>
<tr><td>3</td><td>CHF 600k</td><td>CHF 500k</td><td>+100k</td><td>15 (VPET+SME)</td></tr>
<tr><td>4</td><td>CHF 1.5M</td><td>CHF 700k</td><td>+800k</td><td>40 (inc. 1 enterprise)</td></tr>
<tr><td>5</td><td>CHF 3.5M</td><td>CHF 1M</td><td>+2.5M</td><td>80 (inc. 3 enterprise)</td></tr>
</table>
<h3>Go-to-Market Strategy</h3>
<ol>
<li><strong>Phase 1 (Months 1–12):</strong> Build MVP with 2 VPET pilot partners (e.g., ETH Zürich extension, a cantonal vocational school). Get SWITCHaai integration certified.</li>
<li><strong>Phase 2 (Months 12–24):</strong> Launch on Educa.ch marketplace, present at Swiss EdTech conferences (LEARN, Didacta Switzerland). Target cantonal education IT departments.</li>
<li><strong>Phase 3 (Months 24–36):</strong> Enterprise sales via Simap.ch tenders. Partner with Swiss IT integrators (Swisscom, AdNovum, ELCA) for enterprise deployment.</li>
<li><strong>Phase 4 (Months 36+):</strong> Content marketplace launch; AI-generated Swiss compliance courses (FINMA, data protection, safety).</li>
</ol>
<h3>Key Success Metrics</h3>
<ul>
<li>Time from contract to live tenant: &lt; 48 hours (auto-provisioning)</li>
<li>Net Revenue Retention: &gt; 110% (upsell + expansion)</li>
<li>Gross Margin: &gt; 75% (SaaS economics)</li>
<li>Agentic Content Coverage: 500+ AI-generated courses by Year 3</li>
<li>SWITCHaai Uptime: 99.9% SLA</li>
</ul>',
            ],
        ],
    ],
    6 => [
        'title' => 'Module 6: Decision Framework & Next Steps',
        'label' => '<p><strong>Objective:</strong> Provide a decision-making framework for the Product Manager to evaluate the LMS SaaS opportunity.</p>',
        'pages' => [
            [
                'title' => 'Decision Matrix',
                'content' => '<h3>Should We Build a New Swiss LMS SaaS?</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%;">
<tr style="background:#eee;"><th>Criteria</th><th>Weight</th><th>Score (1–5)</th><th>Weighted</th></tr>
<tr><td>Market Size (CH)</td><td>20%</td><td>3</td><td>0.60</td></tr>
<tr><td>Competitive Intensity</td><td>15%</td><td>4</td><td>0.60</td></tr>
<tr><td>Data Sovereignty Trend</td><td>15%</td><td>5</td><td>0.75</td></tr>
<tr><td>Agentic Cost Advantage</td><td>20%</td><td>4</td><td>0.80</td></tr>
<tr><td>Technical Feasibility</td><td>15%</td><td>4</td><td>0.60</td></tr>
<tr><td>Access to Channels</td><td>15%</td><td>3</td><td>0.45</td></tr>
<tr style="background:#dfd;"><td><strong>Total</strong></td><td>100%</td><td></td><td><strong>3.80 / 5.00</strong></td></tr>
</table>
<p><strong>Interpretation:</strong> Score of 3.8 indicates a <strong>moderately attractive opportunity</strong> with strong differentiation potential. The key drivers are data sovereignty demand and agentic development cost reduction. The main risk is market size — Switzerland is small, so expansion to DACH (Germany, Austria) must be in the long-term roadmap.</p>
<h3>Go / No-Go Thresholds</h3>
<ul>
<li><strong>GO if:</strong> Pilot commitment from 2+ VPET institutions secured; CHF 200k seed funding available; founding team has Swiss EdTech sales experience.</li>
<li><strong>NO-GO if:</strong> No pilot commitments within 6 months; cannot secure SWITCHaai integration partner; competitive response from Moodle Workplace or Canvas within 12 months.</li>
</ul>',
            ],
            [
                'title' => 'Recommended Next Steps',
                'content' => '<h3>Immediate Actions (Weeks 1–4)</h3>
<ol>
<li><strong>Validate Demand:</strong> Interview 10 VPET program directors and 5 corporate L&D managers in Switzerland. Ask: "What would make you switch from Moodle/Canvas?"</li>
<li><strong>Technical Spike:</strong> Build a 2-week agentic prototype of the core LMS (auth + course + AI tutor) using GPT-4 + LangChain. Measure agent output quality.</li>
<li><strong>Legal Review:</strong> Engage Swiss data protection counsel (e.g., MME, Bär & Karrer) to validate FADP architecture assumptions.</li>
<li><strong>Partner Outreach:</strong> Contact SWITCH and Educa.ch to explore partnership / certification pathways.</li>
</ol>
<h3>Short-Term Actions (Months 2–6)</h3>
<ol>
<li>Secure 2 paid pilot contracts (even if discounted) to prove willingness-to-pay.</li>
<li>Develop MVP: multi-tenant SaaS, SWITCHaai SSO, AI course generation, basic SCORM player.</li>
<li>File for Swiss Innosuisse or EUREKA funding (CHF 100k–500k grants available for EdTech).</li>
<li>Build "agentic content factory" prototype: input = PDF syllabus, output = structured Moodle-compatible course with quizzes.</li>
</ol>
<h3>Medium-Term Actions (Months 6–18)</h3>
<ol>
<li>Launch public beta on Swiss market with 10 customers.</li>
<li>Achieve ISO 27001 certification (required for enterprise tenders).</li>
<li>Expand to German-speaking EU (DE, AT) using same data-sovereignty playbook.</li>
<li>Develop proprietary AI models fine-tuned on Swiss educational standards (German/French bilingual).</li>
</ol>
<h3>Risk Mitigation</h3>
<ul>
<li><strong>Market Risk:</strong> Start with VPET (sticky, long contracts) before K-12 (political, slow).</li>
<li><strong>Technical Risk:</strong> Use agentic factory for speed, but hire 1 senior architect for platform decisions.</li>
<li><strong>Competitive Risk:</strong> Differentiate on AI + Swiss compliance; do not compete on price with Moodle.</li>
<li><strong>Funding Risk:</strong> Bootstrap via agentic factory; avoid VC until Series A (Year 2–3).</li>
</ul>',
            ],
        ],
    ],
];

// ------------------------------------------------------------------
// 3. Build / Update Sections
// ------------------------------------------------------------------

echo "Building course sections...\n";

foreach ($sections as $sectionnum => $sectiondata) {
    // Get or create section
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum]);
    if (!$section) {
        $section = new stdClass();
        $section->course  = $course->id;
        $section->section = $sectionnum;
        $section->name    = $sectiondata['title'];
        $section->summary = '';
        $section->visible = 1;
        $section->id = $DB->insert_record('course_sections', $section);
    } else {
        $section->name = $sectiondata['title'];
        $DB->update_record('course_sections', $section);
    }

    echo "Section $sectionnum: {$sectiondata['title']}\n";

    // Add label if present
    if (!empty($sectiondata['label'])) {
        // Check if label already exists in this section
        $existinglabel = $DB->get_record_sql(
            "SELECT cm.id FROM {course_modules} cm
             JOIN {modules} m ON m.id = cm.module
             JOIN {label} l ON l.id = cm.instance
             WHERE cm.course = ? AND cm.section = ? AND m.name = 'label'",
            [$course->id, $section->id]
        );
        if (!$existinglabel) {
            add_label_to_section($course, $sectionnum, $sectiondata['label']);
        }
    }

    // Add pages
    if (!empty($sectiondata['pages'])) {
        foreach ($sectiondata['pages'] as $pageinfo) {
            // Check if page with same name exists in this section
            $existingpage = $DB->get_record_sql(
                "SELECT cm.id FROM {course_modules} cm
                 JOIN {modules} m ON m.id = cm.module
                 JOIN {page} p ON p.id = cm.instance
                 WHERE cm.course = ? AND cm.section = ? AND m.name = 'page' AND p.name = ?",
                [$course->id, $section->id, $pageinfo['title']]
            );
            if (!$existingpage) {
                add_page_to_section($course, $sectionnum, $pageinfo['title'], $pageinfo['content']);
            } else {
                echo "  Page '{$pageinfo['title']}' already exists.\n";
            }
        }
    }
}

echo "\n=== Learning Path Creation Complete ===\n";
echo "Course URL: http://localhost:8080/course/view.php?id=$course->id\n";
exit(0);
