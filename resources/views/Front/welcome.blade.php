
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>StockTrader Pro | Trade Indian Markets With Clarity</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/common.css">
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <div class="container topbar-inner">
                <a href="{{ url('/') }}" class="brand">
                    <div class="brand-mark">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="brand-copy">
                        <strong>AlphaTrader Pro</strong>
                        <span>Indian markets, faster decisions</span>
                    </div>
                </a>

                <nav class="nav-links">
                    <a href="#markets" class="nav-pill">Markets</a>
                    <a href="#platform" class="nav-pill">Platform</a>
                    <a href="#features" class="nav-pill">Features</a>
                    <a href="{{ route('paper.trading') }}" class="ghost-button">Paper Trading</a>
                    <a href="{{ route('zerodha.login') }}" class="primary-button">Connect Zerodha</a>
                </nav>
            </div>
        </header>

        <main class="container">
            @if (session('success') || session('error'))
                <section class="section">
                    <div class="cta-panel" style="margin: 0; padding: 20px 24px;">
                        @if (session('success'))
                            <div style="color: #d8ffe9; font-weight: 700;">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div style="color: #ffd5d5; font-weight: 700;">{{ session('error') }}</div>
                        @endif
                    </div>
                </section>
            @endif

            <section class="hero">
                <div class="hero-copy">
                    <div class="eyebrow">
                        <span class="pulse-dot"></span>
                        Live-ready trading experience for NSE and BSE
                    </div>

                    <h1>Trade with calm. Move with precision.</h1>
                    <p>
                        A sharper trading landing page for your Zerodha-connected platform: cleaner hierarchy, stronger trust cues,
                        and a premium product story built for active investors, swing traders, and portfolio-first users.
                    </p>

                    <div class="hero-actions">
                        <a href="{{ route('zerodha.login') }}" class="primary-button">
                            <i class="fa-solid fa-link"></i>
                            Connect Zerodha
                        </a>
                        <a href="{{ route('paper.trading') }}" class="ghost-button">
                            <i class="fa-solid fa-flask"></i>
                            Open Paper Trading
                        </a>
                    </div>

                    <div class="hero-highlights">
                        <div class="mini-stat">
                            <strong>Fast execution</strong>
                            <span>Built for quick decision loops and cleaner order flow.</span>
                        </div>
                        <div class="mini-stat">
                            <strong>Portfolio clarity</strong>
                            <span>Track holdings, momentum, and allocation in one view.</span>
                        </div>
                        <div class="mini-stat">
                            <strong>India-first UX</strong>
                            <span>Designed around active equity and index traders.</span>
                        </div>
                    </div>
                </div>

                <div class="hero-board">
                    <div class="board-header">
                        <div>
                            <strong>Market Pulse</strong>
                            <span>Real-time feel with a premium landing presentation</span>
                        </div>
                        <div class="status-live">
                            <span class="pulse-dot"></span>
                            Market Live
                        </div>
                    </div>

                    <div class="board-top">
                        <div class="board-value">
                            <strong>&#8377;<span id="portfolio-value">2,58,430</span></strong>
                            <span>Total tracked capital</span>
                        </div>
                        <div class="board-change">
                            +3.18% today<br>
                            <small id="market-time">09:15:00</small>
                        </div>
                    </div>
                    <div class="chart-panel">
                        <div class="chart-grid"></div>
                        <svg class="chart-line" viewBox="0 0 560 220" fill="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="lineGradient" x1="0" y1="0" x2="560" y2="0">
                                    <stop offset="0%" stop-color="#5ce2b0" />
                                    <stop offset="55%" stop-color="#91d3ff" />
                                    <stop offset="100%" stop-color="#57a6ff" />
                                </linearGradient>
                                <linearGradient id="fillGradient" x1="0" y1="0" x2="0" y2="220">
                                    <stop offset="0%" stop-color="rgba(87, 166, 255, 0.45)" />
                                    <stop offset="100%" stop-color="rgba(87, 166, 255, 0)" />
                                </linearGradient>
                            </defs>
                            <path d="M0 173C37 168 55 152 88 144C128 134 138 152 178 133C217 114 238 78 272 86C314 95 327 140 360 128C392 116 398 76 438 65C476 54 500 73 560 34V220H0V173Z" fill="url(#fillGradient)" opacity="0.24"></path>
                            <path d="M0 173C37 168 55 152 88 144C128 134 138 152 178 133C217 114 238 78 272 86C314 95 327 140 360 128C392 116 398 76 438 65C476 54 500 73 560 34" stroke="url(#lineGradient)" stroke-width="6" stroke-linecap="round"></path>
                            <circle cx="438" cy="65" r="7" fill="#5ce2b0"></circle>
                        </svg>
                    </div>

                    <div class="ticker-strip">
                        <div class="ticker-row">
                            <div>
                                <div class="ticker-symbol">NIFTY 50</div>
                                <div class="ticker-meta">Benchmark index</div>
                            </div>
                            <div style="text-align:right;">
                                <div class="ticker-price">22,435.25</div>
                                <div class="up">+0.94%</div>
                            </div>
                        </div>
                        <div class="ticker-row">
                            <div>
                                <div class="ticker-symbol">BANKNIFTY</div>
                                <div class="ticker-meta">Private bank momentum</div>
                            </div>
                            <div style="text-align:right;">
                                <div class="ticker-price">48,126.40</div>
                                <div class="down">-0.22%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section fade-in">
                <div class="grid-4">
                    <article class="metric-card">
                        <strong>Account Opening</strong>
                        <div class="value">5 min</div>
                        <p>Clear onboarding path with immediate action points and less friction.</p>
                    </article>
                    <article class="metric-card">
                        <strong>Watchlist Ready</strong>
                        <div class="value">250+</div>
                        <p>Track curated sectors, index leaders, and key large-cap names quickly.</p>
                    </article>
                    <article class="metric-card">
                        <strong>Portfolio View</strong>
                        <div class="value">1 screen</div>
                        <p>Performance, capital, and market pulse surfaced without clutter.</p>
                    </article>
                    <article class="metric-card">
                        <strong>Experience Grade</strong>
                        <div class="value">Premium</div>
                        <p>Modern, trustworthy landing page direction inspired by top brokers globally.</p>
                    </article>
                </div>
            </section>

            <section class="section fade-in" id="features">
                <div class="section-heading">
                    <h2>Built like a modern brokerage homepage</h2>
                    <p>
                        The new layout follows the strongest patterns used by leading trading platforms: confident hero messaging,
                        proof before noise, and product visuals that sell trust instead of crowding the page.
                    </p>
                </div>

                <div class="grid-3">
                    <article class="feature-card">
                        <div class="feature-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <h3>Cleaner information hierarchy</h3>
                        <p>The first screen now focuses on value, credibility, and actions instead of looking like an internal dashboard.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon"><i class="fa-solid fa-shield-heart"></i></div>
                        <h3>Trust-first visual language</h3>
                        <p>Glass panels, restrained color use, and stronger spacing make the product feel reliable and investment-grade.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <h3>Responsive and device-friendly</h3>
                        <p>Sections stack cleanly on mobile, preserving key CTAs and platform storytelling without overwhelming the user.</p>
                    </article>
                </div>
            </section>

            <section class="section fade-in" id="platform">
                <div class="section-heading">
                    <h2>Show the product, not just the numbers</h2>
                    <p>
                        Premium trading sites usually reveal the platform early. This section gives your landing page a product showcase
                        that feels intentional, high-end, and relevant to users comparing broker experiences.
                    </p>
                </div>

                <div class="platform-layout">
                    <div class="platform-preview">
                        <strong>Trading workspace preview</strong>
                        <p class="muted">A visual story for charts, order flow, and portfolio tracking.</p>

                        <div class="preview-window">
                            <div class="preview-window-header">
                                <div class="window-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <div class="tag">Live Terminal</div>
                            </div>

                            <div class="preview-grid">
                                <div class="preview-panel">
                                    <div class="muted">Intraday signal board</div>
                                    <div class="candles">
                                        <span style="height:44%;"></span>
                                        <span style="height:58%;"></span>
                                        <span style="height:73%;"></span>
                                        <span style="height:60%;"></span>
                                        <span style="height:82%;"></span>
                                        <span style="height:68%;"></span>
                                        <span style="height:88%;"></span>
                                        <span style="height:71%;"></span>
                                        <span style="height:93%;"></span>
                                        <span style="height:84%;"></span>
                                    </div>
                                </div>
                                <div class="preview-panel">
                                    <div class="muted" style="margin-bottom:16px;">Order book</div>
                                    <div class="order-book">
                                        <div class="row"><span>INFY</span><span class="positive">Buy</span></div>
                                        <div class="row"><span>RELIANCE</span><span class="positive">+1.12%</span></div>
                                        <div class="row"><span>TCS</span><span class="negative">-0.45%</span></div>
                                        <div class="row"><span>HDFCBANK</span><span class="positive">+1.87%</span></div>
                                        <div class="row"><span>ICICIBANK</span><span class="positive">+0.92%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="platform-stack">
                        <ul class="platform-list">
                            <li>
                                <div>
                                    <strong>Unified trading flow</strong>
                                    <div class="muted">Move from market discovery to action without visual clutter.</div>
                                </div>
                                <i class="fa-solid fa-arrow-right"></i>
                            </li>
                            <li>
                                <div>
                                    <strong>Better CTA placement</strong>
                                    <div class="muted">Primary actions stay visible where comparison-minded users need them.</div>
                                </div>
                                <i class="fa-solid fa-arrow-right"></i>
                            </li>
                            <li>
                                <div>
                                    <strong>High-intent sections</strong>
                                    <div class="muted">Trust stats, key features, market cards, and platform preview guide the decision.</div>
                                </div>
                                <i class="fa-solid fa-arrow-right"></i>
                            </li>
                            <li>
                                <div>
                                    <strong>Professional visual rhythm</strong>
                                    <div class="muted">Spacing, typography, and contrast now feel closer to a modern fintech brand.</div>
                                </div>
                                <i class="fa-solid fa-arrow-right"></i>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="section fade-in" id="markets">
                <div class="section-heading">
                    <h2>Curated market snapshot</h2>
                    <p>Instead of a crowded stock wall, the new cards focus on legibility, momentum, and quick scanning.</p>
                </div>

                <div class="market-grid">
                    <article class="market-card">
                        <div class="market-card-head">
                            <div class="market-badge"><i class="fa-solid fa-building-columns"></i></div>
                            <span class="tag positive">NSE leader</span>
                        </div>
                        <strong>INFY</strong>
                        <p>Infosys Ltd</p>
                        <div class="price">&#8377;1,562.45</div>
                        <div class="tag positive"><i class="fa-solid fa-arrow-trend-up"></i> +2.34%</div>
                    </article>

                    <article class="market-card">
                        <div class="market-card-head">
                            <div class="market-badge"><i class="fa-solid fa-oil-well"></i></div>
                            <span class="tag positive">Heavyweight</span>
                        </div>
                        <strong>RELIANCE</strong>
                        <p>Reliance Industries</p>
                        <div class="price">&#8377;2,945.80</div>
                        <div class="tag positive"><i class="fa-solid fa-arrow-trend-up"></i> +1.12%</div>
                    </article>

                    <article class="market-card">
                        <div class="market-card-head">
                            <div class="market-badge"><i class="fa-solid fa-laptop-code"></i></div>
                            <span class="tag negative">Pullback</span>
                        </div>
                        <strong>TCS</strong>
                        <p>Tata Consultancy Services</p>
                        <div class="price">&#8377;4,125.60</div>
                        <div class="tag negative"><i class="fa-solid fa-arrow-trend-down"></i> -0.45%</div>
                    </article>
                </div>
            </section>

            <section class="section fade-in">
                <div class="section-heading">
                    <h2>How users move through the experience</h2>
                    <p>A better landing page reduces hesitation. Each section should answer one question and push the next action.</p>
                </div>

                <div class="process-grid">
                    <article class="process-card">
                        <div class="process-card-head">
                            <div class="process-step">01</div>
                            <span class="tag">Discover</span>
                        </div>
                        <h3>Understand the value fast</h3>
                        <p>The headline, trust signals, and market pulse explain the platform within seconds.</p>
                    </article>
                    <article class="process-card">
                        <div class="process-card-head">
                            <div class="process-step">02</div>
                            <span class="tag">Compare</span>
                        </div>
                        <h3>See the platform quality</h3>
                        <p>Product preview and features communicate a higher-end trading experience than plain card grids.</p>
                    </article>
                    <article class="process-card">
                        <div class="process-card-head">
                            <div class="process-step">03</div>
                            <span class="tag">Act</span>
                        </div>
                        <h3>Take the next step confidently</h3>
                        <p>Clear connect and dashboard actions remain visible without competing with too many secondary controls.</p>
                    </article>
                </div>
            </section>

            <section class="section">
                <div class="cta-panel scale-up">
                    <h2>Turn visits into funded accounts with a stronger first impression.</h2>
                    <p>
                        This updated welcome page is designed to feel more global, more premium, and more trustworthy while still fitting
                        an Indian trading product connected to Zerodha workflows.
                    </p>

                    <div class="cta-actions">
                        <a href="{{ route('zerodha.login') }}" class="primary-button">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            Connect Zerodha
                        </a>
                        <a href="{{ route('paper.trading') }}" class="ghost-button">
                            <i class="fa-solid fa-flask"></i>
                            Try Paper Trading
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <div class="container footer-note">
            Refreshed landing experience for a trading website with improved hierarchy, premium styling, and cleaner UX.
        </div>
    </div>

    <script>
        // Add smooth scroll behavior for anchor links
        document.documentElement.style.scrollBehavior = 'smooth';

        // Intersection Observer for fade-in animations
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.addEventListener('DOMContentLoaded', function () {
            // Observe all fade-in elements
            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });

            // Observe scale-up elements
            document.querySelectorAll('.scale-up').forEach(el => {
                observer.observe(el);
            });
        });

        function updateMarketTime() {
            const timeElement = document.getElementById('market-time');

            if (!timeElement) {
                return;
            }

            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        }

        function animatePortfolioValue() {
            const element = document.getElementById('portfolio-value');

            if (!element) {
                return;
            }
            const startValue = 243180;
            const endValue = 258430;
            const duration = 2400;
            const start = performance.now();

            function frame(timestamp) {
                const progress = Math.min((timestamp - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 4);
                const value = Math.floor(startValue + (endValue - startValue) * eased);
                element.textContent = value.toLocaleString('en-IN');

                if (progress < 1) {
                    requestAnimationFrame(frame);
                }
            }

            requestAnimationFrame(frame);
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateMarketTime();
            animatePortfolioValue();
            setInterval(updateMarketTime, 1000);
        });
    </script>
</body>
</html>
