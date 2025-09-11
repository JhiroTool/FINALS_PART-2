<?php
// Simple form handler for demo purposes
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact'])) {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $msg  = htmlspecialchars(trim($_POST['message'] ?? ''));
    if (!$name || !$email || !$msg) {
        $messages[] = ['type' => 'error', 'text' => 'Please fill all fields with valid data.'];
    } else {
        // In production, save to DB or send email. Here we simulate success.
        $messages[] = ['type' => 'success', 'text' => 'Thanks, ' . $name . '! Your message has been received.'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>PinoyFix — Connect. Repair. Empower.</title>
  <meta name="description" content="PinoyFix: a community-driven platform connecting Filipinos with trusted local fixers for home, gadgets, and more." />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header>
    <a class="brand" href="#">
      <img src="images/pinoyfix.png" alt="PinoyFix Logo" class="logo">
      <div>
        <div style="font-weight:800">PinoyFix</div>
        <div style="font-size:12px;color:var(--muted)">Local repairs, Filipino-first</div>
      </div>
    </a>
    <nav>
      <a class="btn btn-ghost" href="#features">Features</a>
      <a class="btn btn-ghost" href="#how">How it works</a>
      <a class="btn btn-primary" href="#get-started">Get started</a>
    </nav>
  </header>

  <main>
    <section class="hero" id="hero">
      <div>
        <span class="badge">Community → Ka-Support</span>
        <h1>PinoyFix — Connect with trusted local fixers in your neighborhood</h1>
        <p class="lead">Fast, transparent, and Filipino-focused platform for home repairs, gadget fixes, and on-site services. Find vetted technicians, see reviews, and schedule same-day visits.</p>
        <div class="cta">
          <a class="btn btn-primary" href="login.php">Find a Fixer</a>
          <a class="btn btn-ghost" href="login.php">Become a Fixer</a>
        </div>

        <div class="cards" style="margin-top:22px">
          <div class="card">
            <div class="icon one">1</div>
            <div>
              <div style="font-weight:700">Quick Booking</div>
              <div style="font-size:13px;color:var(--muted)">Book same-day or schedule ahead with real-time slots.</div>
            </div>
          </div>
          <div class="card">
            <div class="icon two">2</div>
            <div>
              <div style="font-weight:700">Verified Pros</div>
              <div style="font-size:13px;color:var(--muted)">Community-vetted profiles and transparent pricing.</div>
            </div>
          </div>
          <div class="card">
            <div class="icon three">3</div>
            <div>
              <div style="font-weight:700">Secure Payments</div>
              <div style="font-size:13px;color:var(--muted)">Cashless options with escrow until job completion.</div>
            </div>
          </div>
        </div>
      </div>

      <aside class="panel" aria-labelledby="get-started">
        <h3 id="get-started" style="margin:0 0 8px">Start in 3 taps</h3>
        <ol style="padding-left:18px;margin:0 0 12px;color:var(--muted)">
          <li>Tell us the problem (e.g., rice cooker leak)</li>
          <li>Choose a local fixer from matched profiles</li>
          <li>Approve estimate & schedule visit</li>
        </ol>

        <div class="messages">
          <?php foreach ($messages as $m): ?>
            <div class="msg <?php echo $m['type'] === 'success' ? 'success' : 'error' ?>"><?php echo $m['text'] ?></div>
          <?php endforeach; ?>
        </div>

        <form method="post" action="#get-started">
          <input name="name" placeholder="Your name" required />
          <input name="email" type="email" placeholder="Email" required />
          <textarea name="message" placeholder="Briefly describe the issue" rows="4" required></textarea>
          <input type="hidden" name="contact" value="1" />
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary" type="submit">Send Request</button>
            <a class="btn btn-ghost" href="#how" style="align-self:center">Learn how</a>
          </div>
        </form>
      </aside>
    </section>

    <section id="features" style="margin-top:34px">
      <h2 style="margin:0 0 8px">Why PinoyFix?</h2>
      <p style="color:var(--muted);margin:0 0 18px">Designed for Filipino communities — affordable rates, local talents, and support in Tagalog and English.</p>

      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
        <div class="card">
          <div style="font-size:22px; margin-right:12px">🔎</div>
          <div>
            <div style="font-weight:700">Local Matches</div>
            <div style="font-size:13px;color:var(--muted)">Find fixers near you to reduce wait times and travel fees.</div>
          </div>
        </div>
        <div class="card">
          <div style="font-size:22px; margin-right:12px">⭐</div>
          <div>
            <div style="font-weight:700">Ratings & Warranty</div>
            <div style="font-size:13px;color:var(--muted)">Post-job guarantee and customer reviews keep quality high.</div>
          </div>
        </div>
        <div class="card">
          <div style="font-size:22px; margin-right:12px">💬</div>
          <div>
            <div style="font-weight:700">In-app Chat</div>
            <div style="font-size:13px;color:var(--muted)">Coordinate details with the fixer before arrival.</div>
          </div>
        </div>
        <div class="card">
          <div style="font-size:22px; margin-right:12px">🏆</div>
          <div>
            <div style="font-weight:700">Support Local</div>
            <div style="font-size:13px;color:var(--muted)">Empower small, independent Filipino repair businesses.</div>
          </div>
        </div>
      </div>
    </section>

    <section id="how" style="margin-top:28px">
      <h2 style="margin:0 0 8px">How it works</h2>
      <ol style="color:var(--muted);padding-left:18px">
        <li>Post the job with images or a short description.</li>
        <li>Receive 3 local estimates — choose the best match.</li>
        <li>Pay securely and rate the performer after completion.</li>
      </ol>
    </section>

    <section id="testimonials" style="margin-top:34px">
      <h2 style="margin:0 0 8px">What our community says</h2>
      <p style="color:var(--muted);margin:0 0 18px">Real reviews from Filipino families who found reliable fixers through PinoyFix.</p>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">
        <div class="testimonial-card">
          <div class="rating">⭐⭐⭐⭐⭐</div>
          <p>"Kuya Mark fixed our aircon in 2 hours! Very professional and hindi mahal. Highly recommended!"</p>
          <div class="client-info">
            <strong>Maria S.</strong> • Quezon City
            <div style="font-size:12px;color:var(--muted)">Aircon Repair • ₱800</div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="rating">⭐⭐⭐⭐⭐</div>
          <p>"Same-day washing machine repair! Ate Joy was very kind and explained everything clearly. Salamat!"</p>
          <div class="client-info">
            <strong>Roberto L.</strong> • Makati
            <div style="font-size:12px;color:var(--muted)">Appliance Repair • ₱1,200</div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="rating">⭐⭐⭐⭐⭐</div>
          <p>"Fixed my laptop screen perfectly. Very transparent sa pricing and guaranteed ang work niya for 6 months."</p>
          <div class="client-info">
            <strong>Jenny C.</strong> • Cebu City
            <div style="font-size:12px;color:var(--muted)">Gadget Repair • ₱2,500</div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="rating">⭐⭐⭐⭐⭐</div>
          <p>"Electrical wiring sa kitchen. Si Manong Rico very experienced and safety-first talaga. Worth it!"</p>
          <div class="client-info">
            <strong>Carlos M.</strong> • Davao
            <div style="font-size:12px;color:var(--muted)">Electrical Work • ₱3,800</div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="rating">⭐⭐⭐⭐⭐</div>
          <p>"Plumbing emergency solved agad! Ate Linda came within 30 minutes. Professional and mabait pa."</p>
          <div class="client-info">
            <strong>Anna T.</strong> • Pasig
            <div style="font-size:12px;color:var(--muted)">Plumbing • ₱1,500</div>
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="rating">⭐⭐⭐⭐⭐</div>
          <p>"Phone screen replacement done perfectly. Kuya Jun even cleaned the phone for free. Astig!"</p>
          <div class="client-info">
            <strong>Michael P.</strong> • Iloilo
            <div style="font-size:12px;color:var(--muted)">Mobile Repair • ₱1,800</div>
          </div>
        </div>
      </div>
      
      <div style="text-align:center;margin-top:20px">
        <p style="color:var(--muted);margin-bottom:12px">Join thousands of satisfied customers</p>
        <a class="btn btn-primary" href="login.php">Start your repair journey</a>
      </div>
    </section>

    <section id="become" style="margin-top:28px">
      <h2 style="margin:0 0 8px">Become a Fixer</h2>
      <p style="color:var(--muted);margin:0 0 12px">Sign up to grow your client base. Tools for schedules, invoicing, and reputation management included.</p>
      <a class="btn btn-primary" href="register.php">Join the community</a>
    </section>

    <footer>
      <div style="max-width:900px;margin:0 auto;padding:12px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div style="text-align:left">
            <strong>PinoyFix</strong> — Built for Philippine communities.
          </div>
          <div style="font-size:13px;color:var(--muted)">© <?php echo date('Y') ?> PinoyFix • Privacy • Terms</div>
        </div>
      </div>
    </footer>
  </main>

  <script>
    // Small UX: smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a=>{
      a.addEventListener('click', e=>{
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({behavior:'smooth', block:'start'});
        }
      });
    });
  </script>
</body>
</html>