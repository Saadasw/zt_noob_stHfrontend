<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>St. George Hospital Group | Hospital Management System</title>
  <meta name="description" content="Book appointments, access medical records, view lab results, and manage your healthcare online.">
  
  <style>
    /* ============================================
       ST. GEORGE HOSPITAL - STUDENT PROJECT
       Color Scheme: Blue Theme
       ============================================ */
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #333;
      background: #F7F9FC;
      line-height: 1.6;
    }
    
    a {
      color: inherit;
      text-decoration: none;
    }
    
    img {
      max-width: 100%;
      display: block;
    }
    
    /* Container */
    .container {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    /* ============================================
       HEADER / NAVIGATION
       ============================================ */
    header {
      background: #fff;
      border-bottom: 2px solid #4A7BF7;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .header-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      flex-wrap: wrap;
      gap: 10px;
    }
    
    .logo-section {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .logo-box {
      width: 45px;
      height: 45px;
      background: #4A7BF7;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: bold;
      font-size: 20px;
    }
    
    .logo-text h1 {
      font-size: 16px;
      color: #4A7BF7;
      margin: 0;
    }
    
    .logo-text span {
      font-size: 11px;
      color: #6B7280;
    }
    
    nav {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    
    nav a {
      padding: 8px 12px;
      color: #4A7BF7;
      font-size: 14px;
      border-radius: 5px;
    }
    
    nav a:hover {
      background: #EEF2FF;
    }
    
    .btn {
      padding: 10px 18px;
      border-radius: 6px;
      font-weight: bold;
      font-size: 14px;
      cursor: pointer;
      border: none;
      display: inline-block;
    }
    
    .btn-outline {
      background: #EEF2FF;
      color: #4A7BF7;
      border: 1px solid #4A7BF7;
    }
    
    .btn-outline:hover {
      background: #DBEAFE;
    }
    
    .btn-primary {
      background: #4A7BF7;
      color: #fff;
    }
    
    .btn-primary:hover {
      background: #3A5BD9;
    }
    
    .btn-dark {
      background: #2D3748;
      color: #fff;
    }
    
    .btn-dark:hover {
      background: #1A202C;
    }
    
    /* ============================================
       HERO SECTION
       ============================================ */
    .hero {
      background: linear-gradient(135deg, rgba(74,123,247,0.9) 0%, rgba(58,91,217,0.9) 100%),
                  url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1400') center/cover;
      color: #fff;
      padding: 80px 0;
      text-align: center;
    }
    
    .hero h2 {
      font-size: 42px;
      margin-bottom: 15px;
    }
    
    .hero p {
      font-size: 18px;
      max-width: 600px;
      margin: 0 auto 25px;
      opacity: 0.95;
    }
    
    .hero-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
      flex-wrap: wrap;
    }
    
    .hero .btn-dark {
      background: #2D3748;
      color: #fff;
      padding: 14px 28px;
      font-size: 16px;
    }
    
    .hero .btn-dark:hover {
      background: #1A202C;
    }
    
    .hero .btn-outline {
      background: transparent;
      color: #fff;
      border: 2px solid #fff;
      padding: 14px 28px;
      font-size: 16px;
    }
    
    .hero .btn-outline:hover {
      background: rgba(255,255,255,0.1);
    }
    
    /* ============================================
       QUICK ACCESS SECTION
       ============================================ */
    .quick-access {
      background: #3A5BD9;
      padding: 40px 0;
    }
    
    .quick-access h3 {
      color: #fff;
      text-align: center;
      margin-bottom: 25px;
      font-size: 24px;
    }
    
    .quick-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
    }
    
    .quick-card {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
      text-decoration: none;
      color: #333;
    }
    
    .quick-card:hover {
      background: #EEF2FF;
      border: 2px solid #4A7BF7;
    }
    
    .quick-card .icon {
      font-size: 30px;
      margin-bottom: 10px;
    }
    
    .quick-card strong {
      display: block;
      font-size: 15px;
      margin-bottom: 5px;
      color: #2D3748;
    }
    
    .quick-card span {
      font-size: 12px;
      color: #6B7280;
    }
    
    .quick-card:hover strong {
      color: #4A7BF7;
    }
    
    /* ============================================
       SECTION COMMON STYLES
       ============================================ */
    .section {
      padding: 50px 0;
    }
    
    .section-white {
      background: #fff;
    }
    
    .section-gray {
      background: #F7F9FC;
    }
    
    .section-title {
      font-size: 28px;
      color: #2D3748;
      margin-bottom: 10px;
    }
    
    .section-subtitle {
      color: #6B7280;
      margin-bottom: 30px;
    }
    
    /* ============================================
       SERVICES SECTION
       ============================================ */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }
    
    .service-card {
      background: #fff;
      border: 1px solid #E5E7EB;
      border-radius: 10px;
      overflow: hidden;
      text-align: left;
    }
    
    .service-card:hover {
      border-color: #4A7BF7;
    }
    
    .service-card img {
      width: 100%;
      height: 140px;
      object-fit: cover;
    }
    
    .service-card-body {
      padding: 18px;
    }
    
    .service-card h4 {
      color: #2D3748;
      margin-bottom: 10px;
      font-size: 17px;
    }
    
    .service-card p {
      font-size: 13px;
      color: #6B7280;
      margin-bottom: 12px;
    }
    
    .service-card ul {
      font-size: 12px;
      color: #6B7280;
      padding-left: 18px;
      list-style-type: disc;
    }
    
    .service-card li {
      margin-bottom: 4px;
    }
    
    /* ============================================
       ABOUT SECTION
       ============================================ */
    .about-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      align-items: center;
    }
    
    .about-text h3 {
      color: #2D3748;
      font-size: 26px;
      margin-bottom: 15px;
    }
    
    .about-text p {
      margin-bottom: 15px;
      color: #6B7280;
    }
    
    .about-image {
      border-radius: 10px;
      overflow: hidden;
    }
    
    .about-image img {
      width: 100%;
      height: 300px;
      object-fit: cover;
    }
    
    /* ============================================
       BRANCHES SECTION
       ============================================ */
    .branches-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }
    
    .branch-card {
      background: #fff;
      border: 1px solid #E5E7EB;
      border-radius: 10px;
      overflow: hidden;
    }
    
    .branch-card:hover {
      border-color: #4A7BF7;
    }
    
    .branch-card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
    }
    
    .branch-card-body {
      padding: 18px;
    }
    
    .branch-card h4 {
      color: #2D3748;
      margin-bottom: 10px;
      font-size: 18px;
    }
    
    .branch-card p {
      font-size: 13px;
      color: #6B7280;
      margin-bottom: 5px;
    }
    
    .branch-card .badge {
      display: inline-block;
      background: #EEF2FF;
      color: #4A7BF7;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      margin-top: 10px;
    }
    
    /* ============================================
       CONTACT SECTION
       ============================================ */
    .contact-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-top: 20px;
    }
    
    .contact-card {
      background: #2D3748;
      color: #fff;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
    }
    
    .contact-card h4 {
      margin-bottom: 8px;
      font-size: 16px;
    }
    
    .contact-card p {
      font-size: 14px;
      opacity: 0.95;
    }
    
    .contact-card a {
      color: #93C5FD;
    }
    
    /* Emergency Banner */
    .emergency-banner {
      background: #DC2626;
      color: #fff;
      padding: 15px;
      text-align: center;
      margin-top: 25px;
      border-radius: 8px;
    }
    
    .emergency-banner h4 {
      margin-bottom: 5px;
    }
    
    .emergency-banner p {
      font-size: 20px;
      font-weight: bold;
    }
    
    /* ============================================
       FAQ SECTION
       ============================================ */
    .faq-list {
      max-width: 800px;
      margin: 0 auto;
    }
    
    .faq-item {
      background: #fff;
      border: 1px solid #E5E7EB;
      border-radius: 8px;
      margin-bottom: 12px;
      padding: 18px;
    }
    
    .faq-item:hover {
      border-color: #4A7BF7;
    }
    
    .faq-item h4 {
      color: #2D3748;
      font-size: 15px;
      margin-bottom: 8px;
    }
    
    .faq-item p {
      font-size: 13px;
      color: #6B7280;
    }
    
    /* ============================================
       TESTIMONIALS / STORIES SECTION
       ============================================ */
    .stories-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      align-items: center;
    }
    
    .stories-image {
      border-radius: 10px;
      overflow: hidden;
    }
    
    .stories-image img {
      width: 100%;
      height: 280px;
      object-fit: cover;
    }
    
    .stories-text h3 {
      color: #2D3748;
      font-size: 26px;
      margin-bottom: 15px;
    }
    
    .stories-text p {
      color: #6B7280;
      margin-bottom: 15px;
    }
    
    .testimonial-box {
      background: #EEF2FF;
      border-left: 4px solid #4A7BF7;
      padding: 15px;
      border-radius: 0 8px 8px 0;
      margin-top: 15px;
    }
    
    .testimonial-box p {
      font-style: italic;
      color: #4A5568;
      margin-bottom: 8px;
    }
    
    .testimonial-box span {
      font-size: 13px;
      color: #6B7280;
      font-weight: bold;
    }
    
    /* ============================================
       FOOTER
       ============================================ */
    footer {
      background: #2D3748;
      color: #fff;
      padding: 40px 0 20px;
    }
    
    .footer-grid {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr 1fr;
      gap: 30px;
      margin-bottom: 30px;
    }
    
    .footer-brand h3 {
      font-size: 18px;
      margin-bottom: 10px;
    }
    
    .footer-brand p {
      font-size: 12px;
      opacity: 0.85;
    }
    
    .footer-links h4 {
      font-size: 14px;
      margin-bottom: 12px;
      color: #93C5FD;
    }
    
    .footer-links a {
      display: block;
      font-size: 13px;
      margin-bottom: 8px;
      opacity: 0.9;
    }
    
    .footer-links a:hover {
      opacity: 1;
      text-decoration: underline;
    }
    
    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,0.2);
      padding-top: 20px;
      text-align: center;
      font-size: 12px;
      opacity: 0.8;
    }
    
    /* ============================================
       RESPONSIVE DESIGN
       ============================================ */
    @media (max-width: 900px) {
      .quick-grid,
      .services-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .branches-grid,
      .contact-grid {
        grid-template-columns: 1fr;
      }
      
      .about-grid,
      .stories-grid {
        grid-template-columns: 1fr;
      }
      
      .footer-grid {
        grid-template-columns: 1fr 1fr;
      }
      
      .hero h2 {
        font-size: 32px;
      }
    }
    
    @media (max-width: 600px) {
      .header-inner {
        flex-direction: column;
        text-align: center;
      }
      
      nav {
        justify-content: center;
      }
      
      .quick-grid,
      .services-grid {
        grid-template-columns: 1fr;
      }
      
      .hero h2 {
        font-size: 28px;
      }
      
      .hero {
        padding: 50px 0;
      }
      
      .footer-grid {
        grid-template-columns: 1fr;
        text-align: center;
      }
    }
  </style>
</head>

<body>
  <!-- ============================================
       HEADER
       ============================================ -->
  <header>
    <div class="container">
      <div class="header-inner">
        <a href="index.php" class="logo-section">
          <div class="logo-box">+</div>
          <div class="logo-text">
            <h1>St. George Hospital</h1>
            <span>Hospital Management System</span>
          </div>
        </a>
        
        <nav>
          <a href="#home">Home</a>
          <a href="#services">Services</a>
          <a href="#about">About</a>
          <a href="#branches">Branches</a>
          <a href="#faq">FAQ</a>
          <a href="#contact">Contact</a>
          <a href="auth/login.php" class="btn btn-outline">Login</a>
        </nav>
      </div>
    </div>
  </header>
  
  <!-- ============================================
       HERO SECTION
       ============================================ -->
  <section class="hero" id="home">
    <div class="container">
      <h2>Your Health, Our Priority</h2>
      <p>Securely access your medical records, book diagnostic tests, and schedule consultations with our specialists.</p>
      <div class="hero-buttons">
        <a href="auth/login.php" class="btn btn-dark">Sign In to Portal</a>
      </div>
      <p style="margin-top: 20px; font-size: 13px; opacity: 0.85;">
        To view or manage personal health details, you will need to log in first.
      </p>
    </div>
  </section>
  
  <!-- ============================================
       QUICK ACCESS
       ============================================ */ -->
  <section class="quick-access">
    <div class="container">
      <h3>Quick Access</h3>
      <div class="quick-grid">
        <a href="auth/login.php" class="quick-card">
          <div class="icon">📅</div>
          <strong>Appointments</strong>
          <span>View & manage bookings</span>
        </a>
        <a href="auth/login.php" class="quick-card">
          <div class="icon">📋</div>
          <strong>Medical Records</strong>
          <span>Access your health history</span>
        </a>
        <a href="auth/login.php" class="quick-card">
          <div class="icon">🔬</div>
          <strong>Lab Results</strong>
          <span>View test reports</span>
        </a>
        <a href="auth/login.php" class="quick-card">
          <div class="icon">💳</div>
          <strong>Pay Bills</strong>
          <span>Manage payments online</span>
        </a>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       SERVICES SECTION
       ============================================ -->
  <section class="section section-white" id="services">
    <div class="container">
      <h2 class="section-title">Our Services</h2>
      <p class="section-subtitle">Essential healthcare services available across all our branches.</p>
      
      <div class="services-grid">
        <div class="service-card">
          <img src="https://images.unsplash.com/photo-1516549655169-df83a0774514?w=400" alt="Emergency Care">
          <div class="service-card-body">
            <h4>Emergency Care</h4>
            <p>Immediate support for urgent medical conditions.</p>
            <ul>
              <li>24/7 emergency reception</li>
              <li>On-call clinical teams</li>
              <li>Fast response pathways</li>
            </ul>
          </div>
        </div>
        
        <div class="service-card">
          <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400" alt="Diagnostics">
          <div class="service-card-body">
            <h4>Diagnostics</h4>
            <p>Testing and imaging for accurate diagnosis.</p>
            <ul>
              <li>Pathology & blood tests</li>
              <li>X-ray and ultrasound</li>
              <li>Online results via portal</li>
            </ul>
          </div>
        </div>
        
        <div class="service-card">
          <img src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=400" alt="Pharmacy">
          <div class="service-card-body">
            <h4>Pharmacy</h4>
            <p>Medication support and safe dispensing.</p>
            <ul>
              <li>Prescription management</li>
              <li>Medication history</li>
              <li>Safety checks included</li>
            </ul>
          </div>
        </div>
        
        <div class="service-card">
          <img src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=400" alt="Inpatient Care">
          <div class="service-card-body">
            <h4>Inpatient Care</h4>
            <p>Comfortable stays with 24-hour nursing.</p>
            <ul>
              <li>24-hour nursing care</li>
              <li>Patient monitoring</li>
              <li>Discharge planning</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       ABOUT SECTION
       ============================================ -->
  <section class="section section-gray" id="about">
    <div class="container">
      <div class="about-grid">
        <div class="about-text">
          <h3>About St. George Hospital Group</h3>
          <p>
            St. George Hospital Group is a multi-branch healthcare provider serving communities 
            across Australia. We are committed to delivering quality healthcare services with 
            compassion and excellence.
          </p>
          <p>
            Our hospital management system allows patients to book appointments, view medical 
            records, check lab results, and manage their healthcare needs online. Staff members 
            can efficiently manage patient care, scheduling, and administrative tasks through 
            our integrated platform.
          </p>
          <p>
            <strong>Our Mission:</strong> To provide accessible, high-quality healthcare to all 
            patients while embracing modern technology for better health outcomes.
          </p>
          <a href="auth/login.php" class="btn btn-primary" style="margin-top: 10px;">Access Health Portal</a>
        </div>
        <div class="about-image">
          <img src="https://images.unsplash.com/photo-1551076805-e1869033e561?w=600" alt="Hospital Team">
        </div>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       PATIENT STORIES SECTION
       ============================================ -->
  <section class="section section-white">
    <div class="container">
      <div class="stories-grid">
        <div class="stories-image">
          <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600" alt="Patient Care">
        </div>
        <div class="stories-text">
          <h3>Caring for Our Community</h3>
          <p>
            At St. George Hospital, we believe that great healthcare starts with listening to our patients. 
            Every day, our dedicated team of doctors, nurses, and staff work together to provide 
            personalized care that makes a real difference in people's lives.
          </p>
          <p>
            From routine check-ups to complex treatments, we are here for you every step of the way.
          </p>
          
          <div class="testimonial-box">
            <p>"The staff at St. George Hospital were incredibly supportive during my treatment. 
            The online portal made it easy to track my appointments and view my results."</p>
            <span>— Sarah M., Melbourne</span>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       BRANCHES SECTION
       ============================================ -->
  <section class="section section-gray" id="branches">
    <div class="container">
      <h2 class="section-title">Our Branches</h2>
      <p class="section-subtitle">Find a St. George Hospital location near you.</p>
      
      <div class="branches-grid">
        <div class="branch-card">
          <img src="https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?w=400" alt="Melbourne Hospital">
          <div class="branch-card-body">
            <h4>Melbourne CBD</h4>
            <p><strong>Address:</strong> 123 Collins Street, Melbourne VIC 3000</p>
            <p><strong>Phone:</strong> +61 (03) 9000 1234</p>
            <p><strong>Hours:</strong> Open 24/7</p>
            <span class="badge">Main Branch</span>
          </div>
        </div>
        
        <div class="branch-card">
          <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400" alt="Sydney Hospital">
          <div class="branch-card-body">
            <h4>Sydney CBD</h4>
            <p><strong>Address:</strong> 200 George Street, Sydney NSW 2000</p>
            <p><strong>Phone:</strong> +61 (02) 9000 5678</p>
            <p><strong>Hours:</strong> Open 24/7</p>
            <span class="badge">Full Services</span>
          </div>
        </div>
        
        <div class="branch-card">
          <img src="https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=400" alt="Brisbane Hospital">
          <div class="branch-card-body">
            <h4>Brisbane</h4>
            <p><strong>Address:</strong> 300 Adelaide Street, Brisbane QLD 4000</p>
            <p><strong>Phone:</strong> +61 (07) 3000 1234</p>
            <p><strong>Hours:</strong> Open 24/7</p>
            <span class="badge">Full Services</span>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       FAQ SECTION
       ============================================ -->
  <section class="section section-white" id="faq">
    <div class="container">
      <h2 class="section-title" style="text-align: center;">Frequently Asked Questions</h2>
      <p class="section-subtitle" style="text-align: center;">Common questions about our hospital management system.</p>
      
      <div class="faq-list">
        <div class="faq-item">
          <h4>How do I book an appointment?</h4>
          <p>Log in to your patient account and navigate to "Appointments" section. Select your preferred doctor and available time slot. You will receive a confirmation on your dashboard.</p>
        </div>
        
        <div class="faq-item">
          <h4>How can I access my medical records?</h4>
          <p>Log in to your patient account using your registered email and password. Navigate to "Medical Records" section to view your consultation history, prescriptions, and test results.</p>
        </div>
        
        <div class="faq-item">
          <h4>How can I register?</h4>
          <p>Registration is currently handled by hospital staff. Please visit any of our branches to create your account or contact our support.</p>
        </div>
        
        <div class="faq-item">
          <h4>Can I cancel my appointment?</h4>
          <p>Yes, log in to your account and go to "My Appointments" section. Please do this at least 24 hours in advance to avoid any fees.</p>
        </div>
        
        <div class="faq-item">
          <h4>How do I view my results?</h4>
          <p>Log in to your portal and visit the "Lab Results" section. Results are available once they have been verified by our clinical team.</p>
        </div>
        
        <div class="faq-item">
          <h4>Is my personal information secure?</h4>
          <p>Yes, we use industry-standard security measures to protect your data. Our system complies with healthcare data regulations.</p>
        </div>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       CONTACT SECTION
       ============================================ -->
  <section class="section section-gray" id="contact">
    <div class="container">
      <h2 class="section-title">Contact Us</h2>
      <p class="section-subtitle">Get in touch with St. George Hospital Group.</p>
      
      <div class="contact-grid">
        <div class="contact-card">
          <h4>📞 General Enquiries</h4>
          <p>+61 (03) 9000 0000</p>
        </div>
        
        <div class="contact-card">
          <h4>✉️ Email Support</h4>
          <p><a href="mailto:info@stgeorgehospital.com.au">info@stgeorgehospital.com.au</a></p>
        </div>
        
        <div class="contact-card">
          <h4>🕐 Office Hours</h4>
          <p>Mon - Fri: 9:00 AM - 5:00 PM</p>
        </div>
      </div>
      
      <!-- Emergency Banner -->
      <div class="emergency-banner">
        <h4>🚨 EMERGENCY HOTLINE</h4>
        <p>+61 (03) 9000 9999</p>
        <span style="font-size: 13px;">Available 24 hours, 7 days a week</span>
      </div>
    </div>
  </section>
  
  <!-- ============================================
       FOOTER
       ============================================ -->
  <footer>
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <h3>St. George Hospital Group</h3>
          <p>Hospital Management System</p>
          <p style="margin-top: 10px;">Providing quality healthcare services across Australia since 2020.</p>
        </div>
        
        <div class="footer-links">
          <h4>Quick Links</h4>
          <a href="#home">Home</a>
          <a href="#services">Services</a>
          <a href="#about">About Us</a>
          <a href="#branches">Branches</a>
        </div>
        
        <div class="footer-links">
          <h4>Patient Portal</h4>
          <a href="auth/login.php">Login</a>
          <a href="#faq">FAQ</a>
          <a href="#contact">Contact</a>
        </div>
        
        <div class="footer-links">
          <h4>Legal</h4>
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Accessibility</a>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> St. George Hospital Group. Hospital Management System.</p>
        <p style="margin-top: 5px;">Developed for educational purposes.</p>
      </div>
    </div>
  </footer>

</body>
</html>
