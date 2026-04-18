<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paper Trading | Zerodha Kite</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">    
    <link rel="stylesheet" href="/css/trading.css">
</head>
<body class="paper-page h-screen overflow-hidden bg-t-base text-slate-200 font-sans flex flex-col">

    <!-- â•â•â• TOP HEADER BAR â•â•â• -->
    <header class="panel-shell h-14 shrink-0 border-b border-t-border flex items-center px-5 gap-5 z-20 backdrop-blur-sm">
        <!-- Logo -->
        <div class="flex items-center gap-3 pr-5 border-r border-t-border">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 shadow-[0_10px_18px_rgba(37,99,235,0.28)] flex items-center justify-center">
                <i class="fa-solid fa-chart-line text-sm text-white"></i>
            </div>
            <div class="leading-tight">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-blue-300">Paper Lab</p>
                <p class="text-xs text-slate-500 mt-0.5">Strategy Simulator</p>
            </div>
        </div>

        <!-- Paper mode badge -->
        <span class="status-pill shrink-0 text-xs font-semibold rounded-md px-3 py-1.5">
            <i class="fa-solid fa-flask mr-1.5"></i>PAPER MODE
        </span>

        <!-- Header metrics -->
        <div class="flex items-center gap-1.5 ml-2">
            <div class="metric-card flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs">
                <span class="text-slate-500">Strategies</span>
                <span id="metric-strategies" class="font-data font-bold text-slate-100 text-sm">0</span>
            </div>
            <div class="metric-card flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs">
                <span class="text-slate-500">Open P&amp;L</span>
                <span id="metric-open" class="font-data font-bold text-emerald-400 text-sm">INR 0.00</span>
            </div>
            <div class="metric-card flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs">
                <span class="text-slate-500">Closed P&amp;L</span>
                <span id="metric-closed" class="font-data font-bold text-emerald-400 text-sm">INR 0.00</span>
            </div>
        </div>

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Right controls -->
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs text-slate-400">
                <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
                Paper Mode Active
            </span>
            <a href="/stocks" class="ghost-btn text-xs font-medium rounded-md px-3 py-1.5 border transition-colors">
                <i class="fa-solid fa-wifi mr-1.5"></i>Switch to Live
            </a>
        </div>
    </header>

    <!-- â•â•â• MAIN CONTENT AREA â•â•â• -->
    <div class="flex flex-1 overflow-hidden">

        <!--  LEFT SIDEBAR: Watchlist  -->
        <aside id="sidebar" class="panel-shell w-64 shrink-0 border-r border-t-border flex flex-col">
            <!-- Sidebar header -->
            <div class="panel-header px-4 pt-4 pb-3 border-b border-t-border">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400 mb-3">Watchlist</p>
                <input
                    id="symbol-search"
                    type="text"
                    placeholder="Search symbol..."
                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm text-slate-200 placeholder:text-slate-600 focus:outline-none"
                >
                <div class="mt-2.5 flex items-center gap-2.5">
                    <label class="shrink-0 text-xs font-medium text-slate-500">Step (INR)</label>
                    <input
                        id="price-step"
                        type="number"
                        min="0.01"
                        step="0.01"
                        value="5"
                        class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none"
                    >
                </div>
            </div>
            <!-- Script list -->
            <div id="script-list" class="flex-1 overflow-y-auto scroll-thin"></div>
        </aside>

        <!--  CENTER: Main Trading Area  -->
        <main class="grid-bg flex-1 flex flex-col overflow-hidden bg-t-base">

            <!--  PRICE BAR  -->
            <div class="panel-shell panel-header shrink-0 h-16 border-b border-t-border flex items-center px-5 gap-5 backdrop-blur-sm">
                <!-- Symbol info -->
                <div class="flex items-center gap-5 min-w-0">
                    <div>
                        <h2 id="hero-symbol" class="text-2xl font-bold text-white leading-none">--</h2>
                        <p id="hero-name" class="text-xs text-slate-500 mt-1 truncate">Select a script to begin</p>
                    </div>
                    <div class="flex items-center gap-4 pl-5 border-l border-t-border">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 mb-0.5">CMP</p>
                            <p id="hero-price" class="text-3xl font-data font-bold text-emerald-400 leading-none">0.00</p>
                        </div>
                        <div>
                            <p id="hero-change" class="text-base font-data font-bold text-emerald-400 leading-none">+0.00%</p>
                            <p id="hero-time" class="text-xs text-slate-500 mt-1">Base: --</p>
                        </div>
                    </div>
                </div>

                <!-- Spacer -->
                <div class="flex-1"></div>

                <!-- Price simulator controls -->
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Simulator</span>
                    <div class="flex overflow-hidden rounded-xl border border-t-border shadow-[0_8px_18px_rgba(2,6,23,0.22)]">
                        <button id="dir-down" class="btn-sim px-4 py-2 text-xs font-bold text-slate-400">
                            <i class="fa-solid fa-caret-down mr-1.5"></i>Down
                        </button>
                        <button id="dir-auto" class="btn-sim border-x border-t-border px-4 py-2 text-xs font-bold text-slate-400">
                            <i class="fa-solid fa-shuffle mr-1.5"></i>Auto
                        </button>
                        <button id="dir-up" class="btn-sim px-4 py-2 text-xs font-bold text-slate-400">
                            <i class="fa-solid fa-caret-up mr-1.5"></i>Up
                        </button>
                    </div>
                    <button id="reset-prices" class="ghost-btn danger-btn rounded-xl border px-3 py-2 text-xs font-semibold transition-colors">
                        <i class="fa-solid fa-rotate-left mr-1.5"></i>Reset Prices
                    </button>
                </div>
            </div>

            <!--  MIDDLE SECTION: Strategy + Ladder  -->
            <div id="mid-panel" class="shrink-0 flex border-b border-t-border" style="height: 52%;">

                <!-- Strategy Creation Panel -->
                <div class="panel-shell w-80 shrink-0 border-r border-t-border overflow-y-auto scroll-thin">
                    <div class="panel-header px-4 pt-3.5 pb-3 border-b border-t-border flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Create Strategy</p>
                        <span id="selected-script-chip" class="text-xs font-data font-semibold rounded-full bg-blue-500/10 border border-blue-400/20 px-2.5 py-0.5 text-blue-300">--</span>
                    </div>

                    <div class="p-4 space-y-3.5">
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Buy Offset (INR)</span>
                                <input id="buy-offset" type="number" min="0.01" step="0.01" value="5"
                                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Sell Offset (INR)</span>
                                <input id="sell-offset" type="number" min="0.01" step="0.01" value="10"
                                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Lot Size</span>
                                <input id="lot-size" type="number" min="1" step="1" value="25"
                                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Lots Limit</span>
                                <input id="lots-limit" type="number" min="1" step="1" value="5"
                                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Capital Limit (INR)</span>
                            <input id="capital-limit" type="number" min="1" step="1" value="500000"
                                class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                        </label>

                        <!-- Buttons -->
                        <div class="flex gap-2.5 pt-1">
                            <button id="create-strategy"
                                class="primary-btn flex-1 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors">
                                Create Strategy
                            </button>
                            <button id="reset-strategies"
                                class="ghost-btn danger-btn rounded-xl border px-4 py-2.5 text-sm font-semibold transition-colors">
                                Reset
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Lot Ladder Panel -->
                <div class="panel-shell flex-1 overflow-hidden flex flex-col">
                    <div class="panel-header px-4 pt-3.5 pb-3 border-b border-t-border flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Lot Ladder</p>
                        <span id="ladder-summary" class="text-xs font-semibold rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 text-emerald-300">No strategy</span>
                    </div>
                    <div id="lot-ladder" class="flex-1 overflow-y-auto scroll-thin px-3 py-2"></div>
                </div>
            </div>

            
            <!--  DRAG RESIZER  -->
            <div id="panel-resizer" class="shrink-0 h-2" title="Drag to resize panels"></div>

            <!--  BOTTOM TABBED PANEL  -->
            <div class="flex-1 flex flex-col overflow-hidden min-h-0">
                <!-- Tab bar -->
                <div class="panel-shell panel-header shrink-0 flex items-center border-b border-t-border px-2 gap-1">
                    <button class="tab-btn active px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 transition-colors" data-tab="strategies">
                        <i class="fa-solid fa-layer-group mr-2"></i>Strategies
                    </button>
                    <button class="tab-btn px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 transition-colors" data-tab="positions">
                        <i class="fa-solid fa-briefcase mr-2"></i>Positions
                        <span id="open-trade-count" class="ml-2 text-xs rounded-full bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 text-emerald-300 font-data">0</span>
                    </button>
                    <button class="tab-btn px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 transition-colors" data-tab="trades">
                        <i class="fa-solid fa-check-circle mr-2"></i>Closed
                        <span id="trade-count" class="ml-2 text-xs rounded-full bg-sky-500/10 border border-sky-500/30 px-2 py-0.5 text-sky-300 font-data">0</span>
                    </button>
                    <button class="tab-btn px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 transition-colors" data-tab="history">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i>History
                        <span id="all-history-count" class="ml-2 text-xs rounded-full bg-violet-500/10 border border-violet-500/30 px-2 py-0.5 text-violet-300 font-data">0</span>
                    </button>
                </div>

                <!-- Tab contents -->
                <div class="flex-1 overflow-hidden">
                    <!-- Strategies Tab -->
                    <div id="tab-strategies" class="tab-content active h-full overflow-y-auto scroll-thin p-3">
                        <div id="strategy-table" class="grid gap-3"></div>
                    </div>

                    <!-- Positions Tab -->
                    <div id="tab-positions" class="tab-content h-full overflow-y-auto scroll-thin p-3">
                        <div id="open-trades"></div>
                    </div>

                    <!-- Closed Trades Tab -->
                    <div id="tab-trades" class="tab-content h-full overflow-y-auto scroll-thin p-3">
                        <div id="trade-log"></div>
                    </div>

                    <!-- History Tab -->
                    <div id="tab-history" class="tab-content h-full overflow-y-auto scroll-thin p-3">
                        <div id="all-history"></div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- â•â•â• BOTTOM STATUS BAR â•â•â• -->
    <footer class="panel-shell h-9 shrink-0 border-t border-t-border flex items-center px-4 text-xs text-slate-500 gap-4 z-20">
        <span class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            <span class="font-semibold text-emerald-400">Paper Mode</span>
        </span>
        <span class="text-slate-700">|</span>
        <span>Step: <span id="status-step" class="font-data font-semibold text-slate-300">5.00</span></span>
        <span class="text-slate-700">|</span>
        <span>Direction: <span id="status-dir" class="font-data font-bold text-blue-300">AUTO</span></span>
        <div class="flex-1"></div>
        <span class="font-data font-medium text-slate-400" id="status-time"></span>
    </footer>

    <script>
    (function (window) {
        window.initializePaperTrading = function initializePaperTrading(config) {
            var initialStocks = config.stockData || [];
            var $ = function (id) { return document.getElementById(id); };
            var refs = {
                list:              $('script-list'),
                search:            $('symbol-search'),
                heroSymbol:        $('hero-symbol'),
                heroName:          $('hero-name'),
                heroPrice:         $('hero-price'),
                heroChange:        $('hero-change'),
                heroTime:          $('hero-time'),
                metricStrategies:  $('metric-strategies'),
                metricOpen:        $('metric-open'),
                metricClosed:      $('metric-closed'),
                buyOffset:         $('buy-offset'),
                sellOffset:        $('sell-offset'),
                lotSize:           $('lot-size'),
                lotsLimit:         $('lots-limit'),
                capitalLimit:      $('capital-limit'),
                preview:           $('strategy-preview'),
                selectedChip:      $('selected-script-chip'),
                ladderSummary:     $('ladder-summary'),
                lotLadder:         $('lot-ladder'),
                strategyTable:     $('strategy-table'),
                tradeLog:          $('trade-log'),
                tradeCount:        $('trade-count'),
                openTrades:        $('open-trades'),
                openTradeCount:    $('open-trade-count'),
                allHistory:        $('all-history'),
                allHistoryCount:   $('all-history-count'),
                create:            $('create-strategy'),
                reset:             $('reset-strategies'),
                priceStep:         $('price-step'),
                dirDown:           $('dir-down'),
                dirAuto:           $('dir-auto'),
                dirUp:             $('dir-up'),
                resetPrices:       $('reset-prices'),
                statusStep:        $('status-step'),
                statusDir:         $('status-dir'),
                statusTime:        $('status-time'),
            };

            var state = {
                stocks: initialStocks.map(function (stock) {
                    return Object.assign({}, stock, {
                        displayName: stock.symbol,
                        basePrice:   stock.price,
                        change:      0,
                    });
                }),
                selectedSymbol:    initialStocks[0] ? initialStocks[0].symbol : null,
                selectedStrategyId: null,
                strategies:        [],
                trades:            [],
            };

            var autoWalkInterval = null;
            var autoWalkDir      = 'down';
            var renderPending    = false;
            var activeTab        = 'strategies';

            //  Tab system 
            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeTab = btn.dataset.tab;
                    document.querySelectorAll('.tab-btn').forEach(function (b) {
                        b.classList.toggle('active', b.dataset.tab === activeTab);
                    });
                    document.querySelectorAll('.tab-content').forEach(function (c) {
                        c.classList.toggle('active', c.id === 'tab-' + activeTab);
                    });
                });
            });

            //  Status bar clock 
            function updateClock() {
                if (refs.statusTime) {
                    refs.statusTime.textContent = new Date().toLocaleTimeString();
                }
            }
            setInterval(updateClock, 1000);
            updateClock();

            //  helpers 

            function esc(str) {
                return String(str == null ? '' : str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function scheduleRender() {
                if (!renderPending) {
                    renderPending = true;
                    requestAnimationFrame(function () {
                        renderPending = false;
                        render();
                    });
                }
            }

            function money(value) {
                return Number(value || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function pct(value) {
                return (value >= 0 ? '+' : '') + Number(value || 0).toFixed(2) + '%';
            }

            function getStep() {
                return Math.max(0.01, Number(refs.priceStep.value) || 5);
            }

            //  state accessors 

            function selectedStock() {
                return state.stocks.find(function (s) {
                    return s.symbol === state.selectedSymbol;
                }) || state.stocks[0] || null;
            }

            function selectedStrategies() {
                return state.strategies.filter(function (s) {
                    return s.symbol === state.selectedSymbol;
                });
            }

            function selectedStrategy() {
                return state.strategies.find(function (s) {
                    return s.id === state.selectedStrategyId;
                }) || selectedStrategies()[0] || null;
            }

            function form() {
                return {
                    buyOffset:    Number(refs.buyOffset.value),
                    sellOffset:   Number(refs.sellOffset.value),
                    lotSize:      Number(refs.lotSize.value),
                    lotsLimit:    Number(refs.lotsLimit.value),
                    capitalLimit: Number(refs.capitalLimit.value),
                };
            }

            //  strategy logic (UNCHANGED) 

            function levels(price, settings) {
                return Array.from({ length: settings.lotsLimit }, function (_, index) {
                    var buyPrice = Number((price - (index * settings.buyOffset)).toFixed(2));
                    return {
                        level:       index + 1,
                        buyPrice:    buyPrice,
                        targetPrice: Number((buyPrice + settings.sellOffset).toFixed(2)),
                    };
                });
            }

            function metrics(strategy, currentPrice) {
                var held = strategy.levels.filter(function (l) { return l.status === 'held'; });
                var pending = strategy.levels.filter(function (l) { return l.status === 'pending'; });
                var committed = strategy.levels
                    .filter(function (l) { return l.status !== 'closed'; })
                    .reduce(function (sum, l) { return sum + (l.buyPrice * l.qty); }, 0);
                return {
                    held:      held.length,
                    pending:   pending.length,
                    committed: committed,
                    remaining: Math.max(strategy.capitalLimit - committed, 0),
                    openPnl:   held.reduce(function (sum, l) {
                        return sum + ((currentPrice - l.buyPrice) * l.qty);
                    }, 0),
                };
            }


            function recycleLevel(strategy, soldLevel, livePrice) {
                var isTop = soldLevel.buyPrice === strategy.topBuyPrice;

                if (isTop) {
                    var newTopBuy    = soldLevel.targetPrice;
                    var newTopTarget = Number((newTopBuy + strategy.sellOffset).toFixed(2));

                    // Drop all stale pending levels â€” anchored to the old base price
                    strategy.levels = strategy.levels.filter(function (l) {
                        return l.status !== 'pending';
                    });

                    // Step the top up to the new sell price
                    strategy.topBuyPrice = newTopBuy;

                    // New top level â€” immediately held at the new base
                    var topLevel = {
                        level:       strategy.recycleCount++,
                        buyPrice:    newTopBuy,
                        targetPrice: newTopTarget,
                        qty:         strategy.lotSize,
                        status:      'held',
                        closedPnl:   0,
                    };
                    strategy.levels.push(topLevel);

                    // Rebuild pending levels below the new top to fill up to lotsLimit
                    var activeCount   = strategy.levels.filter(function (l) { return l.status !== 'closed'; }).length;
                    var pendingNeeded = Math.max(0, strategy.lotsLimit - activeCount);
                    for (var i = 1; i <= pendingNeeded; i++) {
                        var pBuy    = Number((newTopBuy - (i * strategy.buyOffset)).toFixed(2));
                        var pTarget = Number((pBuy + strategy.sellOffset).toFixed(2));
                        strategy.levels.push({
                            level:       strategy.recycleCount++,
                            buyPrice:    pBuy,
                            targetPrice: pTarget,
                            qty:         strategy.lotSize,
                            status:      'pending',
                            closedPnl:   0,
                        });
                    }
                } else {
                    // Normal level recycle â€” re-queue at the same buy price
                    var buyPrice    = soldLevel.buyPrice;
                    var targetPrice = Number((buyPrice + strategy.sellOffset).toFixed(2));
                    strategy.levels.push({
                        level:       strategy.recycleCount++,
                        buyPrice:    buyPrice,
                        targetPrice: targetPrice,
                        qty:         strategy.lotSize,
                        status:      'pending',
                        closedPnl:   0,
                    });
                }
            }

            function runSimulation(symbol, livePrice) {
                state.strategies
                    .filter(function (strategy) { return strategy.symbol === symbol; })
                    .forEach(function (strategy) {
                        strategy.levels.forEach(function (level) {
                            var committed;

                            if (level.status === 'pending' && livePrice <= level.buyPrice) {
                                committed = strategy.levels
                                    .filter(function (item) { return item.status !== 'closed'; })
                                    .reduce(function (sum, item) { return sum + (item.buyPrice * item.qty); }, 0);

                                if (committed + (level.buyPrice * level.qty) <= strategy.capitalLimit) {
                                    level.status = 'held';
                                }
                            }

                            if (level.status === 'held' && livePrice >= level.targetPrice) {
                                level.status    = 'closed';
                                level.closedPnl = (level.targetPrice - level.buyPrice) * level.qty;
                                strategy.closedPnl += level.closedPnl;

                                state.trades.push({
                                    strategyId:   strategy.id,
                                    strategyName: strategy.name,
                                    symbol:       symbol,
                                    level:        level.level,
                                    qty:          level.qty,
                                    buyPrice:     level.buyPrice,
                                    sellPrice:    level.targetPrice,
                                    pnl:          level.closedPnl,
                                    closedAt:     Date.now(),
                                });

                                recycleLevel(strategy, level, livePrice);
                            }
                        });
                    });
            }

            function adjustStockPrice(symbol, delta) {
                var stock = state.stocks.find(function (s) { return s.symbol === symbol; });
                if (!stock) { return; }
                stock.price  = Math.max(0.01, Number((stock.price + delta).toFixed(2)));
                stock.change = stock.basePrice > 0
                    ? ((stock.price - stock.basePrice) * 100) / stock.basePrice
                    : 0;
                runSimulation(symbol, stock.price);
                scheduleRender();
            }

            //  render functions (REDESIGNED) 

            function renderList() {
                var query  = refs.search.value.trim().toLowerCase();
                var stocks = state.stocks.filter(function (stock) {
                    return stock.symbol.toLowerCase().indexOf(query) !== -1;
                });

                refs.list.innerHTML = stocks.map(function (stock) {
                    var selected = stock.symbol === state.selectedSymbol;
                    var isPos = stock.change >= 0;
                    var changeColor = isPos ? 'text-emerald-400' : 'text-rose-400';
                    var absChange = Number(stock.price - stock.basePrice).toFixed(2);
                    var hasStrat = state.strategies.some(function (s) { return s.symbol === stock.symbol; });
                    return ''
                        + '<div data-symbol="' + esc(stock.symbol) + '" class="stock-item flex items-center justify-between px-4 py-3 cursor-pointer ' + (selected ? 'selected ' : '') + '">'
                        + '<div class="min-w-0 flex-1">'
                        + '<div class="flex items-center gap-1.5">'
                        + '<p class="text-sm font-bold text-white truncate">' + esc(stock.symbol) + '</p>'
                        + (hasStrat ? '<span class="strat-dot"></span>' : '')
                        + '</div>'
                        + '<p class="text-[10px] text-slate-600 font-data mt-0.5">Base ' + money(stock.basePrice) + '</p>'
                        + '</div>'
                        + '<div class="text-right shrink-0 pl-2">'
                        + '<p class="text-sm font-data font-bold text-white leading-none">' + money(stock.price) + '</p>'
                        + '<p class="text-[10px] font-data mt-1 ' + changeColor + '">'
                        + (isPos ? '+' : '') + absChange + ' <span class="text-[9px] opacity-70">(' + pct(stock.change) + ')</span>'
                        + '</p>'
                        + '</div>'
                        + '</div>';
                }).join('') || '<div class="px-4 py-5 text-xs text-slate-500 text-center">No symbols found</div>';

                refs.list.querySelectorAll('[data-symbol]').forEach(function (item) {
                    item.addEventListener('click', function () {
                        state.selectedSymbol     = item.dataset.symbol;
                        var strats               = state.strategies.filter(function (s) { return s.symbol === state.selectedSymbol; });
                        state.selectedStrategyId = strats[0] ? strats[0].id : null;
                        render();
                    });
                });
            }

            function renderHero() {
                var stock = selectedStock();
                if (!stock) { return; }
                refs.heroSymbol.textContent = stock.symbol;
                refs.heroName.textContent   = stock.displayName + '  paper workspace';
                refs.heroPrice.textContent  = money(stock.price);
                refs.heroChange.textContent = pct(stock.change);
                refs.heroChange.className    = 'text-base font-data font-bold leading-none ' + (stock.change >= 0 ? 'text-emerald-400' : 'text-rose-400');
                refs.heroTime.textContent    = 'Base: ' + money(stock.basePrice);
                refs.selectedChip.textContent = stock.symbol;
            }

            function renderPreview() {
                if (!refs.preview) { return; }
                if (selectedStrategies().length > 0) { refs.preview.innerHTML = ''; return; }
                var stock    = selectedStock();
                var settings = form();
                if (!stock || Object.values(settings).some(function (v) {
                    return !Number.isFinite(v) || v <= 0;
                })) {
                    refs.preview.innerHTML = '';
                    return;
                }
                var ladder        = levels(stock.price, settings);
                var totalCapital  = ladder.reduce(function (sum, l) { return sum + (l.buyPrice * settings.lotSize); }, 0);
                var perTrade      = settings.sellOffset * settings.lotSize;
                var maxProfit     = perTrade * settings.lotsLimit;

                refs.preview.innerHTML = ''
                    + '<div class="section-divider"></div>'
                    + '<p class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-600 mb-2">Strategy Preview</p>'
                    + '<div style="display:flex;flex-direction:column;gap:2px;">'
                    + ladder.map(function (l, i) {
                        var profit = (l.targetPrice - l.buyPrice) * settings.lotSize;
                        return '<div class="preview-level">'
                            + '<span class="preview-level-num">L' + (i + 1) + '</span>'
                            + '<span class="preview-level-buy">' + money(l.buyPrice) + '</span>'
                            + '<span class="preview-level-arrow">&#8594;</span>'
                            + '<span class="preview-level-target">' + money(l.targetPrice) + '</span>'
                            + '<span class="preview-level-profit">+' + money(profit) + '</span>'
                            + '</div>';
                    }).join('')
                    + '</div>'
                    + '<div class="preview-capital-row">'
                    + '<div><span class="preview-capital-label">Capital Required</span></div>'
                    + '<div style="display:flex;gap:12px;align-items:center;">'
                    + '<span class="preview-capital-value">' + money(totalCapital) + '</span>'
                    + '<span style="color:#334155;font-size:10px;">Max +' + money(maxProfit) + '/cycle</span>'
                    + '</div>'
                    + '</div>';
            }

            function renderMetrics() {
                var currentPrice = selectedStock() ? selectedStock().price : 0;
                var strategies   = selectedStrategies();
                var open   = strategies.reduce(function (sum, s) { return sum + metrics(s, currentPrice).openPnl; }, 0);
                var closed = strategies.reduce(function (sum, s) { return sum + s.closedPnl; }, 0);

                refs.metricStrategies.textContent = String(strategies.length);
                refs.metricOpen.textContent       = money(open);
                refs.metricClosed.textContent     = money(closed);
                refs.metricOpen.className   = 'font-data font-bold text-sm ' + (open   >= 0 ? 'text-emerald-400' : 'text-rose-400');
                refs.metricClosed.className = 'font-data font-bold text-sm ' + (closed >= 0 ? 'text-emerald-400' : 'text-rose-400');

                // Status bar
                if (refs.statusStep) refs.statusStep.textContent = getStep().toFixed(2);
                if (refs.statusDir) refs.statusDir.textContent = autoWalkDir.toUpperCase();
            }

            function renderLadder() {
                var strategy = selectedStrategy();
                var currentPrice, lm;

                if (!strategy) {
                    refs.ladderSummary.textContent = 'No strategy';
                    refs.lotLadder.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-layer-group"></i><p><strong>No Strategy Active</strong>Create a strategy to see the lot ladder</p></div>';
                    return;
                }

                currentPrice = (state.stocks.find(function (s) { return s.symbol === strategy.symbol; }) || {}).price || strategy.basePrice;
                lm = metrics(strategy, currentPrice);
                refs.ladderSummary.textContent = strategy.name + ' | ' + lm.held + 'H / ' + lm.pending + 'P';

                var header = ''
                    + '<div class="grid grid-cols-[40px_1fr_1fr_52px_68px_88px] gap-2 px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-slate-600 border-b border-t-border mb-1">'
                    + '<span>Lvl</span><span>Buy → Target</span><span></span><span class="text-right">Qty</span><span class="text-right">Status</span><span class="text-right">P&amp;L</span>'
                    + '</div>';

                var activeLevels = strategy.levels
                    .filter(function (level) { return level.status !== 'closed'; })
                    .slice()
                    .sort(function (a, b) { return b.buyPrice - a.buyPrice; });

                var priceMarkerInserted = false;
                var rows = [];

                activeLevels.forEach(function (level) {
                    if (!priceMarkerInserted && currentPrice >= level.buyPrice) {
                        priceMarkerInserted = true;
                        rows.push(
                            '<div class="price-marker">'
                            + '<span class="price-marker-label">CMP</span>'
                            + '<span class="price-marker-line"></span>'
                            + '<span class="price-marker-value">' + money(currentPrice) + '</span>'
                            + '</div>'
                        );
                    }

                    var pnl = level.status === 'held' ? (currentPrice - level.buyPrice) * level.qty : level.closedPnl;
                    var pnlClass = pnl >= 0 ? 'pnl-pos' : 'pnl-neg';
                    var statusColor, statusText;
                    if (level.status === 'held') {
                        statusColor = 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30';
                        statusText = 'HELD';
                    } else {
                        statusColor = 'bg-amber-500/15 text-amber-300 border border-amber-500/30';
                        statusText = 'PENDING';
                    }
                    var rowClass = 'ladder-row is-' + level.status + ' grid grid-cols-[40px_1fr_1fr_52px_68px_88px] gap-2 items-center px-3 py-2 rounded-xl text-sm bg-t-raised/45';

                    var buyCell = '<div><span class="font-data font-bold text-emerald-400">' + money(level.buyPrice) + '</span>';
                    if (level.status === 'held') {
                        var range = level.targetPrice - level.buyPrice;
                        var progress = range > 0 ? Math.max(0, Math.min(100, ((currentPrice - level.buyPrice) / range) * 100)) : 0;
                        buyCell += '<div class="target-bar"><div class="target-bar-inner" style="width:' + progress.toFixed(0) + '%"></div></div>';
                    } else {
                        var dist = currentPrice - level.buyPrice;
                        var distClass = dist < strategy.buyOffset * 0.75 ? 'dist-chip very-near' : dist < strategy.buyOffset * 1.5 ? 'dist-chip near' : 'dist-chip';
                        buyCell += '<div><span class="' + distClass + '">-' + money(dist) + '</span></div>';
                    }
                    buyCell += '</div>';

                    rows.push(''
                        + '<div class="' + rowClass + '">'
                        + '<span class="font-data font-bold text-slate-500">' + level.level + '</span>'
                        + buyCell
                        + '<span class="font-data font-bold text-amber-300">' + money(level.targetPrice) + '</span>'
                        + '<span class="font-data text-right text-slate-400 font-medium">' + level.qty + '</span>'
                        + '<span class="text-right"><span class="text-[9px] font-bold rounded px-1.5 py-0.5 ' + statusColor + '">' + statusText + '</span></span>'
                        + '<span class="font-data text-right font-bold text-sm ' + pnlClass + '">' + money(pnl) + '</span>'
                        + '</div>'
                    );
                });

                if (!priceMarkerInserted) {
                    rows.push(
                        '<div class="price-marker">'
                        + '<span class="price-marker-label">CMP</span>'
                        + '<span class="price-marker-line"></span>'
                        + '<span class="price-marker-value">' + money(currentPrice) + '</span>'
                        + '</div>'
                    );
                }

                refs.lotLadder.innerHTML = header + rows.join('');
            }

            function renderStrategies() {
                var strategies = selectedStrategies();
                if (!strategies.length) {
                    refs.strategyTable.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-layer-group"></i><p><strong>No Active Strategies</strong>Select a script and create a strategy to begin simulation</p></div>';
                    return;
                }
                refs.strategyTable.innerHTML = strategies.map(function (strategy) {
                    var currentPrice = selectedStock() ? selectedStock().price : strategy.basePrice;
                    var sm    = metrics(strategy, currentPrice);
                    var total = strategy.closedPnl + sm.openPnl;
                    var bar   = strategy.capitalLimit ? Math.min((sm.committed / strategy.capitalLimit) * 100, 100) : 0;
                    var isSelected = strategy.id === state.selectedStrategyId;
                    return ''
                        + '<div data-strategy="' + strategy.id + '" class="strategy-card rounded-2xl border p-4 '
                        + (isSelected ? 'selected border-blue-400/50 ' : 'border-t-border bg-t-raised/50')
                        + '">'
                        + '<div class="flex items-start justify-between mb-3">'
                        + '<div>'
                        + '<p class="text-sm font-bold text-white">' + esc(strategy.name) + '</p>'
                        + '<p class="text-xs text-slate-500 mt-1">Buy INR ' + strategy.buyOffset + ' | Sell INR ' + strategy.sellOffset + ' | Lot ' + strategy.lotSize + '</p>'
                        + '</div>'
                        + '<div class="text-right">'
                        + '<p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Total P&amp;L</p>'
                        + '<p class="text-base font-data font-bold ' + (total >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(total) + '</p>'
                        + '</div>'
                        + '</div>'
                        + '<div class="grid grid-cols-4 gap-2 mb-3">'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Base</p><p class="text-sm font-data font-bold text-white">' + money(strategy.basePrice) + '</p></div>'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Held / Pend</p><p class="text-sm font-data font-bold text-white">' + sm.held + ' / ' + sm.pending + '</p></div>'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Committed</p><p class="text-sm font-data font-bold text-white">' + money(sm.committed) + '</p></div>'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Remaining</p><p class="text-sm font-data font-bold text-white">' + money(sm.remaining) + '</p></div>'
                        + '</div>'
                        + '<div class="flex items-center gap-2.5">'
                        + '<div class="flex-1 h-2 rounded-full bg-t-base overflow-hidden border border-t-border/60">'
                        + '<div class="capital-bar h-full rounded-full ' + (bar > 80 ? 'bg-rose-400' : bar > 50 ? 'bg-amber-400' : 'bg-blue-400') + '" style="width:' + bar + '%"></div>'
                        + '</div>'
                        + '<span class="text-xs font-data font-semibold text-slate-400 shrink-0">' + bar.toFixed(0) + '%</span>'
                        + '</div>'
                        + '</div>';
                }).join('');

                refs.strategyTable.querySelectorAll('[data-strategy]').forEach(function (row) {
                    row.addEventListener('click', function () {
                        state.selectedStrategyId = Number(row.dataset.strategy);
                        renderLadder();
                        renderStrategies();
                    });
                });
            }

            function renderOpenTrades() {
                var openPositions = [];
                state.strategies
                    .filter(function (s) { return s.symbol === state.selectedSymbol; })
                    .forEach(function (strategy) {
                        strategy.levels
                            .filter(function (l) { return l.status === 'held'; })
                            .forEach(function (level) {
                                var stock = state.stocks.find(function (s) { return s.symbol === strategy.symbol; });
                                var currentPrice = stock ? stock.price : strategy.basePrice;
                                var unrealizedPnl = (currentPrice - level.buyPrice) * level.qty;
                                openPositions.push({
                                    strategyName: strategy.name,
                                    level: level.level,
                                    symbol: strategy.symbol,
                                    buyPrice: level.buyPrice,
                                    targetPrice: level.targetPrice,
                                    currentPrice: currentPrice,
                                    qty: level.qty,
                                    unrealizedPnl: unrealizedPnl,
                                });
                            });
                    });

                var totalPnl = openPositions.reduce(function (sum, p) { return sum + p.unrealizedPnl; }, 0);
                refs.openTradeCount.textContent = String(openPositions.length);

                if (!openPositions.length) {
                    refs.openTrades.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-briefcase"></i><p><strong>No Open Positions</strong>Positions appear when price hits a buy level</p></div>';
                    return;
                }

                refs.openTrades.innerHTML = ''
                    + '<div class="flex items-center justify-between rounded-xl bg-emerald-500/8 border border-emerald-500/15 px-3 py-2 mb-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">'
                    + '<span class="text-[10px] text-slate-400">Total Unrealized P&amp;L</span>'
                    + '<span class="text-sm font-data font-bold ' + (totalPnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(totalPnl) + '</span>'
                    + '</div>'
                    + '<div class="overflow-x-auto">'
                    + '<table class="w-full text-[11px]">'
                    + '<thead><tr class="border-b border-t-border text-[9px] uppercase tracking-wider text-slate-500">'
                    + '<th class="pb-2 pr-3 text-left">Symbol</th>'
                    + '<th class="pb-2 pr-3 text-left">Strategy</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Buy</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">CMP</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Target</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Qty</th>'
                    + '<th class="pb-2 text-right font-data">P&amp;L</th>'
                    + '</tr></thead>'
                    + '<tbody>'
                    + openPositions.map(function (p) {
                        return ''
                            + '<tr class="row-hover border-b border-t-border/50">'
                            + '<td class="py-2 pr-3 font-semibold text-white">' + esc(p.symbol) + '</td>'
                            + '<td class="py-2 pr-3 text-slate-400">' + esc(p.strategyName) + ' &middot;L' + p.level + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + money(p.buyPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data font-medium text-white">' + money(p.currentPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-amber-300">' + money(p.targetPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + p.qty + '</td>'
                            + '<td class="py-2 text-right font-data font-semibold ' + (p.unrealizedPnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(p.unrealizedPnl) + '</td>'
                            + '</tr>';
                    }).join('')
                    + '</tbody></table></div>';
            }

            function renderTrades() {
                var trades = state.trades
                    .filter(function (t) { return t.symbol === state.selectedSymbol; })
                    .slice()
                    .reverse();
                var totalPnl = trades.reduce(function (sum, t) { return sum + t.pnl; }, 0);
                refs.tradeCount.textContent = String(trades.length);
                if (!trades.length) {
                    refs.tradeLog.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-check-circle"></i><p><strong>No Closed Trades</strong>Completed trades appear when target price is hit</p></div>';
                    return;
                }
                refs.tradeLog.innerHTML = ''
                    + '<div class="flex items-center justify-between rounded-xl bg-sky-500/8 border border-sky-500/15 px-3 py-2 mb-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">'
                    + '<span class="text-[10px] text-slate-400">Total Realized P&amp;L</span>'
                    + '<span class="text-sm font-data font-bold ' + (totalPnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(totalPnl) + '</span>'
                    + '</div>'
                    + '<div class="overflow-x-auto">'
                    + '<table class="w-full text-[11px]">'
                    + '<thead><tr class="border-b border-t-border text-[9px] uppercase tracking-wider text-slate-500">'
                    + '<th class="pb-2 pr-3 text-left">Symbol</th>'
                    + '<th class="pb-2 pr-3 text-left">Strategy</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Buy</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Sell</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Qty</th>'
                    + '<th class="pb-2 text-right font-data">P&amp;L</th>'
                    + '</tr></thead>'
                    + '<tbody>'
                    + trades.map(function (trade) {
                        return ''
                            + '<tr class="row-hover border-b border-t-border/50">'
                            + '<td class="py-2 pr-3 font-semibold text-white">' + esc(trade.symbol) + '</td>'
                            + '<td class="py-2 pr-3 text-slate-400">' + esc(trade.strategyName) + ' &middot;L' + trade.level + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + money(trade.buyPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + money(trade.sellPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + trade.qty + '</td>'
                            + '<td class="py-2 text-right font-data font-semibold ' + (trade.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(trade.pnl) + '</td>'
                            + '</tr>';
                    }).join('')
                    + '</tbody></table></div>';
            }

            function renderAllHistory() {
                var history = [];

                state.strategies
                    .filter(function (s) { return s.symbol === state.selectedSymbol; })
                    .forEach(function (strategy) {
                        strategy.levels
                            .filter(function (l) { return l.status === 'held'; })
                            .forEach(function (level) {
                                var stock = state.stocks.find(function (s) { return s.symbol === strategy.symbol; });
                                var currentPrice = stock ? stock.price : strategy.basePrice;
                                history.push({
                                    type: 'OPEN',
                                    strategyName: strategy.name,
                                    symbol: strategy.symbol,
                                    level: level.level,
                                    buyPrice: level.buyPrice,
                                    exitPrice: currentPrice,
                                    qty: level.qty,
                                    pnl: (currentPrice - level.buyPrice) * level.qty,
                                    time: null,
                                });
                            });
                    });

                state.trades
                    .filter(function (t) { return t.symbol === state.selectedSymbol; })
                    .forEach(function (trade) {
                        history.push({
                            type: 'CLOSED',
                            strategyName: trade.strategyName,
                            symbol: trade.symbol,
                            level: trade.level,
                            buyPrice: trade.buyPrice,
                            exitPrice: trade.sellPrice,
                            qty: trade.qty,
                            pnl: trade.pnl,
                            time: trade.closedAt,
                        });
                    });

                history.sort(function (a, b) {
                    if (a.type === 'OPEN' && b.type !== 'OPEN') return -1;
                    if (a.type !== 'OPEN' && b.type === 'OPEN') return 1;
                    if (a.time && b.time) return b.time - a.time;
                    return 0;
                });

                refs.allHistoryCount.textContent = String(history.length);

                if (!history.length) {
                    refs.allHistory.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-clock-rotate-left"></i><p><strong>No Trade History</strong>All open and closed positions appear here</p></div>';
                    return;
                }

                refs.allHistory.innerHTML = ''
                    + '<div class="overflow-x-auto">'
                    + '<table class="w-full text-[11px]">'
                    + '<thead><tr class="border-b border-t-border text-[9px] uppercase tracking-wider text-slate-500">'
                    + '<th class="pb-2 pr-3 text-left">Status</th>'
                    + '<th class="pb-2 pr-3 text-left">Symbol</th>'
                    + '<th class="pb-2 pr-3 text-left">Strategy</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Buy</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Exit</th>'
                    + '<th class="pb-2 pr-3 text-right font-data">Qty</th>'
                    + '<th class="pb-2 text-right font-data">P&amp;L</th>'
                    + '</tr></thead>'
                    + '<tbody>'
                    + history.map(function (h) {
                        var statusBadge = h.type === 'OPEN'
                            ? '<span class="text-[9px] rounded-md px-1.5 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">OPEN</span>'
                            : '<span class="text-[9px] rounded-md px-1.5 py-0.5 bg-sky-500/10 border border-sky-500/20 text-sky-300 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">CLOSED</span>';
                        return ''
                            + '<tr class="row-hover border-b border-t-border/50">'
                            + '<td class="py-2 pr-3">' + statusBadge + '</td>'
                            + '<td class="py-2 pr-3 font-semibold text-white">' + esc(h.symbol) + '</td>'
                            + '<td class="py-2 pr-3 text-slate-400">' + esc(h.strategyName) + ' &middot;L' + h.level + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + money(h.buyPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + money(h.exitPrice) + '</td>'
                            + '<td class="py-2 pr-3 text-right font-data text-slate-300">' + h.qty + '</td>'
                            + '<td class="py-2 text-right font-data font-semibold ' + (h.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(h.pnl) + '</td>'
                            + '</tr>';
                    }).join('')
                    + '</tbody></table></div>';
            }


            function renderCreateButton() {
                var active = selectedStrategies().length > 0;
                refs.create.disabled    = active;
                refs.create.className   = active
                    ? 'flex-1 rounded-xl px-4 py-2.5 text-sm font-bold cursor-not-allowed bg-slate-800/90 text-slate-500 border border-t-border'
                    : 'primary-btn flex-1 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors';
                refs.create.textContent = active ? 'Strategy Already Running' : 'Create Strategy';
            }

            function render() {
                renderCreateButton();
                renderList();
                renderHero();
                renderPreview();
                renderMetrics();
                renderLadder();
                renderStrategies();
                renderOpenTrades();
                renderTrades();
                renderAllHistory();
            }

            //  strategy creation (UNCHANGED) 

            function createStrategy() {
                if (selectedStrategies().length > 0) { return; }

                var stock    = selectedStock();
                var settings = form();
                var strategy;

                if (!stock || Object.values(settings).some(function (v) {
                    return !Number.isFinite(v) || v <= 0;
                })) { return; }

                strategy = {
                    id:           Date.now(),
                    name:         stock.symbol,
                    symbol:       stock.symbol,
                    basePrice:    stock.price,
                    buyOffset:    settings.buyOffset,
                    sellOffset:   settings.sellOffset,
                    lotSize:      settings.lotSize,
                    lotsLimit:    settings.lotsLimit,
                    capitalLimit: settings.capitalLimit,
                    closedPnl:    0,
                    recycleCount: settings.lotsLimit + 1,
                    topBuyPrice:  stock.price,
                    levels:       levels(stock.price, settings).map(function (level, index) {
                        return Object.assign({}, level, {
                            qty:       settings.lotSize,
                            status:    index === 0 ? 'held' : 'pending',
                            closedPnl: 0,
                        });
                    }),
                };

                state.strategies.push(strategy);
                state.selectedStrategyId = strategy.id;
                render();
            }

            //  price simulator (UNCHANGED) 

            function updateDirectionButtons() {
                refs.dirDown.className = 'btn-sim px-4 py-2 text-xs font-bold text-slate-400 transition-colors '
                    + (autoWalkDir === 'down' ? 'active-down' : '');
                refs.dirAuto.className = 'btn-sim border-x border-t-border px-4 py-2 text-xs font-bold text-slate-400 transition-colors '
                    + (autoWalkDir === 'auto' ? 'active-auto' : '');
                refs.dirUp.className = 'btn-sim px-4 py-2 text-xs font-bold text-slate-400 transition-colors '
                    + (autoWalkDir === 'up' ? 'active-up' : '');
            }

            function startAutoWalk(dir) {
                if (autoWalkInterval) {
                    clearInterval(autoWalkInterval);
                    autoWalkInterval = null;
                }
                autoWalkDir = dir;
                updateDirectionButtons();
                autoWalkInterval = setInterval(function () {
                    var step  = getStep();
                    var delta = dir === 'up'   ? step
                              : dir === 'down' ? -step
                              : (Math.random() < 0.5 ? step : -step);
                    state.stocks.forEach(function (stock) {
                        adjustStockPrice(stock.symbol, delta);
                    });
                }, 800);
            }

            //  event listeners 

            refs.search.addEventListener('input', function () { renderList(); });

            refs.create.addEventListener('click', createStrategy);

            refs.reset.addEventListener('click', function () {
                state.strategies        = [];
                state.trades            = [];
                state.selectedStrategyId = null;
                render();
            });

            refs.dirDown.addEventListener('click', function () { startAutoWalk('down'); });
            refs.dirAuto.addEventListener('click', function () { startAutoWalk('auto'); });
            refs.dirUp.addEventListener('click',   function () { startAutoWalk('up'); });

            refs.resetPrices.addEventListener('click', function () {
                var currentDir = autoWalkDir;
                if (autoWalkInterval) { clearInterval(autoWalkInterval); autoWalkInterval = null; }
                state.stocks.forEach(function (stock) {
                    stock.price  = stock.basePrice;
                    stock.change = 0;
                });
                render();
                startAutoWalk(currentDir);
            });

            [
                refs.buyOffset,
                refs.sellOffset,
                refs.lotSize,
                refs.lotsLimit,
                refs.capitalLimit,
            ].forEach(function (input) { input.addEventListener('input', renderPreview); });

            //  Panel resizer drag 
            (function () {
                var resizer  = $('panel-resizer');
                var midPanel = $('mid-panel');
                var mainEl   = midPanel.parentElement;
                var dragging = false;
                var startY   = 0;
                var startH   = 0;

                resizer.addEventListener('mousedown', function (e) {
                    dragging = true;
                    startY   = e.clientY;
                    startH   = midPanel.offsetHeight;
                    document.body.classList.add('resizing');
                    e.preventDefault();
                });

                document.addEventListener('mousemove', function (e) {
                    if (!dragging) { return; }
                    var delta  = e.clientY - startY;
                    var minH   = 160;
                    var maxH   = mainEl.offsetHeight - 100;
                    var newH   = Math.max(minH, Math.min(maxH, startH + delta));
                    midPanel.style.height = newH + 'px';
                });

                document.addEventListener('mouseup', function () {
                    if (dragging) {
                        dragging = false;
                        document.body.classList.remove('resizing');
                    }
                });
            })();

            render();
            startAutoWalk('auto');
        };
    })(window);

    window.initializePaperTrading({
        stockData: @json($stockData),
    });
    </script>
</body>
</html>

