<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Trading | Zerodha Kite</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/trading.css">
</head>
<body class="live-page h-screen overflow-hidden bg-t-base text-slate-200 font-sans flex flex-col">
    <header class="panel-shell h-14 shrink-0 border-b border-t-border flex items-center px-5 gap-5 z-20 backdrop-blur-sm">
        <div class="flex items-center gap-3 pr-5 border-r border-t-border">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 shadow-[0_10px_18px_rgba(37,99,235,0.28)] flex items-center justify-center">
                <i class="fa-solid fa-chart-line text-sm text-white"></i>
            </div>
            <div class="leading-tight">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-blue-300">Live Desk</p>
                <p class="text-xs text-slate-500 mt-0.5">Strategy Simulator</p>
            </div>
        </div>

        <span class="status-pill shrink-0 text-xs font-semibold rounded-md px-3 py-1.5">
            <i class="fa-solid fa-signal mr-1.5"></i>LIVE MODE
        </span>

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

        <div class="flex-1"></div>

        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-xs text-slate-400">
                <i id="status-dot" class="fa-solid fa-circle text-slate-500"></i>
                <span id="status-text">Connecting...</span>
            </span>
            <span id="last-updated" class="text-xs text-slate-500">Waiting for first tick...</span>
            <form method="POST" action="{{ route('zerodha.logout') }}">
                @csrf
                <button type="submit" class="ghost-btn text-xs font-medium rounded-md px-3 py-1.5 border transition-colors">
                    <i class="fa-solid fa-right-from-bracket mr-1.5"></i>Disconnect
                </button>
            </form>
            <a href="/paper/trading" class="ghost-btn text-xs font-medium rounded-md px-3 py-1.5 border transition-colors">
                <i class="fa-solid fa-flask mr-1.5"></i>Switch to Paper
            </a>
        </div>
    </header>

    @if (session('success'))
        <div class="shrink-0 border-b border-emerald-500/20 bg-emerald-950/40 px-5 py-3 text-sm text-emerald-200">
            <span class="font-semibold">Success:</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (isset($error))
        <div class="shrink-0 border-b border-rose-500/20 bg-rose-950/40 px-5 py-3 text-sm text-rose-200">
            <span class="font-semibold">API Connection Error:</span>
            <span>{{ $error }}</span>
        </div>
    @endif

    <div class="flex flex-1 overflow-hidden">
        <aside id="sidebar" class="panel-shell w-64 shrink-0 border-r border-t-border flex flex-col">
            <div class="panel-header px-4 pt-4 pb-3 border-b border-t-border">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400 mb-3">Watchlist</p>
                <input
                    id="symbol-search"
                    type="text"
                    placeholder="Search symbol..."
                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm text-slate-200 placeholder:text-slate-600 focus:outline-none"
                >
                <div class="mt-3 rounded-xl border border-t-border bg-t-base/80 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">Feed Status</p>
                    <p id="sidebar-feed-status" class="mt-1 text-xs font-medium text-slate-300">Waiting for live ticks</p>
                </div>
            </div>
            <div id="script-list" class="flex-1 overflow-y-auto scroll-thin"></div>
        </aside>

        <main class="grid-bg flex-1 flex flex-col overflow-hidden bg-t-base">
            <div class="panel-shell panel-header shrink-0 h-16 border-b border-t-border flex items-center px-5 gap-5 backdrop-blur-sm">
                <div class="flex items-center gap-5 min-w-0">
                    <div>
                        <h2 id="hero-symbol" class="text-2xl font-bold text-white leading-none">--</h2>
                        <p id="hero-name" class="text-xs text-slate-500 mt-1 truncate">Waiting for live market data</p>
                    </div>
                    <div class="flex items-center gap-4 pl-5 border-l border-t-border">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 mb-0.5">CMP</p>
                            <p id="hero-price" class="text-3xl font-data font-bold text-emerald-400 leading-none">0.00</p>
                        </div>
                        <div>
                            <p id="hero-change" class="text-base font-data font-bold text-emerald-400 leading-none">+0.00%</p>
                            <p id="hero-time" class="text-xs text-slate-500 mt-1">Last tick: --</p>
                        </div>
                    </div>
                </div>

                <div class="flex-1"></div>

                <div class="flex items-center gap-3">
                    <div class="metric-card rounded-xl px-4 py-2 text-xs">
                        <p class="text-slate-500 uppercase tracking-[0.2em]">Connection</p>
                        <p id="hero-status" class="mt-1 font-data font-semibold text-slate-200">CONNECTING</p>
                    </div>
                    <div class="metric-card rounded-xl px-4 py-2 text-xs">
                        <p class="text-slate-500 uppercase tracking-[0.2em]">Heartbeat</p>
                        <p id="hero-heartbeat" class="mt-1 font-data font-semibold text-slate-200">WAITING</p>
                    </div>
                </div>
            </div>

            <div id="mid-panel" class="shrink-0 flex border-b border-t-border" style="height: 52%;">
                <div class="panel-shell w-80 shrink-0 border-r border-t-border overflow-y-auto scroll-thin">
                    <div class="panel-header px-4 pt-3.5 pb-3 border-b border-t-border flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Create Strategy</p>
                        <span id="selected-script-chip" class="text-xs font-data font-semibold rounded-full bg-blue-500/10 border border-blue-400/20 px-2.5 py-0.5 text-blue-300">--</span>
                    </div>

                    <div class="p-4 space-y-3.5">
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Buy Offset (INR)</span>
                                <input id="buy-offset" type="number" min="0.01" step="0.01" value="1"
                                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Sell Offset (INR)</span>
                                <input id="sell-offset" type="number" min="0.01" step="0.01" value="5"
                                    class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5 block">Lot Size</span>
                                <input id="lot-size" type="number" min="1" step="1" value="1"
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
                            <input id="capital-limit" type="number" min="1" step="1" value="5000"
                                class="w-full rounded-xl border border-t-border bg-t-base px-3 py-2.5 text-sm font-data text-slate-200 focus:outline-none">
                        </label>

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

                <div class="panel-shell flex-1 overflow-hidden flex flex-col">
                    <div class="panel-header px-4 pt-3.5 pb-3 border-b border-t-border flex items-center justify-between">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Lot Ladder</p>
                        <span id="ladder-summary" class="text-xs font-semibold rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 text-emerald-300">No strategy</span>
                    </div>
                    <div id="lot-ladder" class="flex-1 overflow-y-auto scroll-thin px-3 py-2"></div>
                </div>
            </div>

            <div id="panel-resizer" class="shrink-0 h-2" title="Drag to resize panels"></div>

            <div class="flex-1 flex flex-col overflow-hidden min-h-0">
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
                    <!-- <button class="tab-btn px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 transition-colors" data-tab="history">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i>History
                        <span id="all-history-count" class="ml-2 text-xs rounded-full bg-violet-500/10 border border-violet-500/30 px-2 py-0.5 text-violet-300 font-data">0</span>
                    </button> -->
                </div>

                <div class="flex-1 overflow-hidden">
                    <div id="tab-strategies" class="tab-content active h-full overflow-y-auto scroll-thin p-3">
                        <div id="strategy-table" class="grid gap-3"></div>
                    </div>
                    <div id="tab-positions" class="tab-content h-full overflow-y-auto scroll-thin p-3">
                        <div id="open-trades"></div>
                    </div>
                    <div id="tab-trades" class="tab-content h-full overflow-y-auto scroll-thin p-3">
                        <div id="trade-log"></div>
                    </div>
                    <div id="tab-history" class="tab-content h-full overflow-y-auto scroll-thin p-3">
                        <div id="all-history"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer class="panel-shell h-9 shrink-0 border-t border-t-border flex items-center px-4 text-xs text-slate-500 gap-4 z-20">
        <span class="flex items-center gap-2">
            <i id="footer-status-dot" class="fa-solid fa-circle text-slate-500"></i>
            <span id="footer-status-text" class="font-semibold text-slate-300">Connecting...</span>
        </span>
        <span class="text-slate-700">|</span>
        <span>Mode: <span class="font-data font-semibold text-slate-300">LIVE</span></span>
        <span class="text-slate-700">|</span>
        <span>Selected: <span id="footer-symbol" class="font-data font-bold text-blue-300">--</span></span>
        <div class="flex-1"></div>
        <span class="font-data font-medium text-slate-400" id="status-time">Waiting for first tick...</span>
    </footer>

    <script>
    (function (window) {
        function bufferToLong(buffer) {
            return Array.from(new Uint8Array(buffer)).reduceRight(function (value, byte, index, bytes) {
                return value + (byte << ((bytes.length - 1 - index) * 8));
            }, 0);
        }

        function parseTicks(buffer) {
            var count = bufferToLong(buffer.slice(0, 2));
            var cursor = 2;
            var ticks = [];

            for (var index = 0; index < count; index += 1) {
                var size = bufferToLong(buffer.slice(cursor, cursor + 2));
                var packet = buffer.slice(cursor + 2, cursor + 2 + size);
                var token = bufferToLong(packet.slice(0, 4));
                var segment = token & 0xff;
                var divisor = segment === 3 ? 10000000 : 100;
                var lastPrice = bufferToLong(packet.slice(4, 8)) / divisor;
                var close = packet.byteLength === 8 ? 0 : bufferToLong((packet.byteLength === 28 || packet.byteLength === 32) ? packet.slice(20, 24) : packet.slice(40, 44)) / divisor;

                ticks.push({
                    instrument_token: token,
                    last_price: lastPrice,
                    change: close ? ((lastPrice - close) * 100) / close : 0,
                });

                cursor += size + 2;
            }

            return ticks;
        }

        function createKiteLivePriceSocket(options) {
            var socket = null;
            var reconnectCount = 0;

            function updateStatus(colorClass, text) {
                if (typeof options.onStatusChange === 'function') {
                    options.onStatusChange(colorClass, text);
                }
            }

            function connect() {
                var url;

                if (!options.apiKey || !options.accessToken || !options.tokens.length) {
                    return;
                }

                url = 'wss://ws.kite.trade/?api_key=' + options.apiKey + '&access_token=' + options.accessToken + '&uid=' + Date.now();
                socket = new WebSocket(url);
                socket.binaryType = 'arraybuffer';

                socket.onopen = function () {
                    reconnectCount = 0;
                    updateStatus('text-emerald-400', 'Connected');
                    this.send(JSON.stringify({ a: 'subscribe', v: options.tokens }));
                    this.send(JSON.stringify({ a: 'mode', v: ['ltp', options.tokens] }));
                };

                socket.onmessage = function (event) {
                    if (typeof event.data === 'string') {
                        return;
                    }
                    if (typeof options.onTicks === 'function') {
                        options.onTicks(parseTicks(event.data));
                    }
                };

                socket.onerror = function (error) {
                    updateStatus('text-rose-400', 'Feed Error');
                    if (typeof options.onError === 'function') {
                        options.onError(error);
                    }
                };

                socket.onclose = function () {
                    var delay;
                    reconnectCount += 1;
                    updateStatus('text-amber-400', 'Reconnecting');
                    delay = Math.min(Math.pow(2, reconnectCount), 30);
                    if (typeof options.onReconnect === 'function') {
                        options.onReconnect(delay, reconnectCount);
                    }
                    window.setTimeout(connect, delay * 1000);
                };
            }

            function disconnect() {
                if (socket && socket.readyState !== WebSocket.CLOSED) {
                    socket.close();
                }
            }

            return {
                connect: connect,
                disconnect: disconnect,
            };
        }

        window.createKiteLivePriceSocket = createKiteLivePriceSocket;
    })(window);

    (function (window) {
        window.initializeStocksSimulator = function initializeStocksSimulator(config) {
            var initialStocks = config.stockData || [];
            var apiKey = config.apiKey || '';
            var accessToken = config.accessToken || '';
            var createStrategyUrl = config.createStrategyUrl || '';
            var symbolDataUrl = config.symbolDataUrl || '';
            var csrfToken = config.csrfToken || '';
            var $ = function (id) { return document.getElementById(id); };
            var refs = {
                list: $('script-list'),
                search: $('symbol-search'),
                heroSymbol: $('hero-symbol'),
                heroName: $('hero-name'),
                heroPrice: $('hero-price'),
                heroChange: $('hero-change'),
                heroTime: $('hero-time'),
                heroStatus: $('hero-status'),
                heroHeartbeat: $('hero-heartbeat'),
                metricStrategies: $('metric-strategies'),
                metricOpen: $('metric-open'),
                metricClosed: $('metric-closed'),
                selectedChip: $('selected-script-chip'),
                ladderSummary: $('ladder-summary'),
                lotLadder: $('lot-ladder'),
                strategyTable: $('strategy-table'),
                tradeLog: $('trade-log'),
                tradeCount: $('trade-count'),
                openTrades: $('open-trades'),
                openTradeCount: $('open-trade-count'),
                allHistory: $('all-history'),
                allHistoryCount: $('all-history-count'),
                create: $('create-strategy'),
                reset: $('reset-strategies'),
                buyOffset: $('buy-offset'),
                sellOffset: $('sell-offset'),
                lotSize: $('lot-size'),
                lotsLimit: $('lots-limit'),
                capitalLimit: $('capital-limit'),
                statusDot: $('status-dot'),
                statusText: $('status-text'),
                lastUpdated: $('last-updated'),
                footerStatusDot: $('footer-status-dot'),
                footerStatusText: $('footer-status-text'),
                statusTime: $('status-time'),
                footerSymbol: $('footer-symbol'),
                sidebarFeedStatus: $('sidebar-feed-status')
            };

            var state = {
                stocks: initialStocks.map(function (stock) {
                    return Object.assign({}, stock, {
                        displayName: stock.symbol,
                        basePrice: Number(stock.close || stock.price || 0),
                        change: Number(stock.change || 0),
                        lastTickAt: null
                    });
                }),
                selectedSymbol: initialStocks[0] ? initialStocks[0].symbol : null,
                selectedStrategyId: null,
                strategies: [],
                trades: [],
                lotLadder: {
                    symbol: null,
                    summary: 'No strategy',
                    rows: [],
                    loading: false,
                    error: null
                },
                positions: {
                    symbol: null,
                    rows: [],
                    loading: false,
                    error: null
                },
                closedTrades: {
                    symbol: null,
                    rows: [],
                    loading: false,
                    error: null
                },
                lastTickAt: null,
                livePriceSocket: null
            };

            var renderPending = false;
            var activeTab = 'strategies';

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
                return 'INR ' + Number(value || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function moneyPlain(value) {
                return Number(value || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function pct(value) {
                return (value >= 0 ? '+' : '') + Number(value || 0).toFixed(2) + '%';
            }

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

            async function fetchSymbolData(symbol) {
                var targetSymbol = symbol || state.selectedSymbol;
                var response;
                var result;

                if (!symbolDataUrl || !targetSymbol) {
                    return;
                }

                state.lotLadder.symbol = targetSymbol;
                state.lotLadder.loading = true;
                state.lotLadder.error = null;
                state.positions.symbol = targetSymbol;
                state.positions.loading = true;
                state.positions.error = null;
                state.closedTrades.symbol = targetSymbol;
                state.closedTrades.loading = true;
                state.closedTrades.error = null;
                scheduleRender();

                try {
                    response = await window.fetch(symbolDataUrl + '?symbol=' + encodeURIComponent(targetSymbol), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to load symbol data.');
                    }

                    state.lotLadder.symbol = targetSymbol;
                    state.lotLadder.summary = (result.lot_ladder && result.lot_ladder.summary) || 'No strategy';
                    state.lotLadder.rows = result.lot_ladder && Array.isArray(result.lot_ladder.rows) ? result.lot_ladder.rows : [];
                    state.lotLadder.error = null;

                    state.positions.symbol = targetSymbol;
                    state.positions.rows = Array.isArray(result.positions) ? result.positions : [];
                    state.positions.error = null;

                    state.closedTrades.symbol = targetSymbol;
                    state.closedTrades.rows = Array.isArray(result.closed_trades) ? result.closed_trades : [];
                    state.closedTrades.error = null;
                } catch (error) {
                    state.lotLadder.symbol = targetSymbol;
                    state.lotLadder.summary = 'Lot ladder unavailable';
                    state.lotLadder.rows = [];
                    state.lotLadder.error = error && error.message ? error.message : 'Unable to load symbol data.';

                    state.positions.symbol = targetSymbol;
                    state.positions.rows = [];
                    state.positions.error = error && error.message ? error.message : 'Unable to load symbol data.';

                    state.closedTrades.symbol = targetSymbol;
                    state.closedTrades.rows = [];
                    state.closedTrades.error = error && error.message ? error.message : 'Unable to load symbol data.';
                } finally {
                    state.lotLadder.loading = false;
                    state.positions.loading = false;
                    state.closedTrades.loading = false;
                    scheduleRender();
                }
            }

            function form() {
                return {
                    buyOffset: Number(refs.buyOffset.value),
                    sellOffset: Number(refs.sellOffset.value),
                    lotSize: Number(refs.lotSize.value),
                    lotsLimit: Number(refs.lotsLimit.value),
                    capitalLimit: Number(refs.capitalLimit.value),
                };
            }

            function levels(price, settings) {
                return Array.from({ length: settings.lotsLimit }, function (_, index) {
                    var buyPrice = Number((price - (index * settings.buyOffset)).toFixed(2));
                    return {
                        level: index + 1,
                        buyPrice: buyPrice,
                        targetPrice: Number((buyPrice + settings.sellOffset).toFixed(2)),
                    };
                });
            }

            function metrics(strategy, currentPrice) {
                var held = strategy.levels.filter(function (l) { return l.status === 'held'; });
                var pending = strategy.levels.filter(function (l) { return l.status === 'pending'; });
                var committed = strategy.levels.filter(function (l) { return l.status !== 'closed'; }).reduce(function (sum, l) { return sum + (l.buyPrice * l.qty); }, 0);
                return {
                    held: held.length,
                    pending: pending.length,
                    committed: committed,
                    remaining: Math.max(strategy.capitalLimit - committed, 0),
                    openPnl: held.reduce(function (sum, l) { return sum + ((currentPrice - l.buyPrice) * l.qty); }, 0),
                };
            }

            function recycleLevel(strategy, soldLevel) {
                var isTop = soldLevel.buyPrice === strategy.topBuyPrice;

                if (isTop) {
                    var newTopBuy = soldLevel.targetPrice;
                    var newTopTarget = Number((newTopBuy + strategy.sellOffset).toFixed(2));
                    strategy.levels = strategy.levels.filter(function (l) { return l.status !== 'pending'; });
                    strategy.topBuyPrice = newTopBuy;
                    strategy.levels.push({
                        level: strategy.recycleCount++,
                        buyPrice: newTopBuy,
                        targetPrice: newTopTarget,
                        qty: strategy.lotSize,
                        status: 'held',
                        closedPnl: 0,
                    });
                    var activeCount = strategy.levels.filter(function (l) { return l.status !== 'closed'; }).length;
                    var pendingNeeded = Math.max(0, strategy.lotsLimit - activeCount);
                    for (var i = 1; i <= pendingNeeded; i++) {
                        var pBuy = Number((newTopBuy - (i * strategy.buyOffset)).toFixed(2));
                        var pTarget = Number((pBuy + strategy.sellOffset).toFixed(2));
                        strategy.levels.push({
                            level: strategy.recycleCount++,
                            buyPrice: pBuy,
                            targetPrice: pTarget,
                            qty: strategy.lotSize,
                            status: 'pending',
                            closedPnl: 0,
                        });
                    }
                } else {
                    var buyPrice = soldLevel.buyPrice;
                    var targetPrice = Number((buyPrice + strategy.sellOffset).toFixed(2));
                    strategy.levels.push({
                        level: strategy.recycleCount++,
                        buyPrice: buyPrice,
                        targetPrice: targetPrice,
                        qty: strategy.lotSize,
                        status: 'pending',
                        closedPnl: 0,
                    });
                }
            }
            function setStatus(colorClass, text) {
                refs.statusDot.className = 'fa-solid fa-circle ' + colorClass;
                refs.footerStatusDot.className = 'fa-solid fa-circle ' + colorClass;
                refs.statusText.textContent = text;
                refs.footerStatusText.textContent = text;
                refs.heroStatus.textContent = text.toUpperCase();
                refs.heroStatus.className = 'mt-1 font-data font-semibold ' + (colorClass === 'text-emerald-400' ? 'text-emerald-300' : colorClass === 'text-amber-400' ? 'text-amber-300' : colorClass === 'text-rose-400' ? 'text-rose-300' : 'text-slate-200');
                refs.sidebarFeedStatus.textContent = text;
                refs.sidebarFeedStatus.className = 'mt-1 text-xs font-medium ' + (colorClass === 'text-emerald-400' ? 'text-emerald-300' : colorClass === 'text-amber-400' ? 'text-amber-300' : colorClass === 'text-rose-400' ? 'text-rose-300' : 'text-slate-300');
            }

            function refreshHeartbeat() {
                var seconds;
                var text;

                if (!state.lastTickAt) {
                    text = 'Waiting for first tick...';
                    refs.lastUpdated.textContent = text;
                    refs.statusTime.textContent = text;
                    refs.heroHeartbeat.textContent = 'WAITING';
                    refs.heroHeartbeat.className = 'mt-1 font-data font-semibold text-slate-200';
                    return;
                }

                seconds = Math.max(0, Math.floor((Date.now() - state.lastTickAt) / 1000));
                text = seconds === 0 ? 'Updated just now' : 'Updated ' + seconds + 's ago';
                refs.lastUpdated.textContent = text;
                refs.statusTime.textContent = text;
                refs.heroHeartbeat.textContent = seconds <= 3 ? 'LIVE' : 'STALE';
                refs.heroHeartbeat.className = 'mt-1 font-data font-semibold ' + (seconds <= 3 ? 'text-emerald-300' : 'text-amber-300');
            }

            function renderList() {
                var query = refs.search.value.trim().toLowerCase();
                var stocks = state.stocks.filter(function (stock) {
                    return stock.symbol.toLowerCase().indexOf(query) !== -1;
                });

                refs.list.innerHTML = stocks.map(function (stock) {
                    var selected = stock.symbol === state.selectedSymbol;
                    var hasStrat = state.strategies.some(function (s) { return s.symbol === stock.symbol; });
                    var priceDiff = Number(stock.price || 0) - Number(stock.basePrice || 0);
                    var percentDiff = Number(stock.basePrice || 0) > 0
                        ? (priceDiff * 100) / Number(stock.basePrice || 0)
                        : Number(stock.change || 0);
                    var isPos = priceDiff >= 0;
                    var changeColor = isPos ? 'text-emerald-400' : 'text-rose-400';
                    var priceChange = priceDiff.toFixed(2);
                    var percentChange = percentDiff.toFixed(2) + '%';
                    return ''
                        + '<div data-symbol="' + esc(stock.symbol) + '" class="stock-item flex items-center justify-between px-4 py-3 cursor-pointer ' + (selected ? 'selected ' : '') + '">'
                        + '<div class="min-w-0 flex-1">'
                        + '<div class="flex items-center gap-1.5">'
                        + '<p class="text-sm font-bold text-white truncate">' + esc(stock.symbol) + '</p>'
                        + (hasStrat ? '<span class="strat-dot"></span>' : '')
                        + '</div>'
                        + '</div>'
                        + '<div class="text-right shrink-0 pl-2">'
                        + '<p class="text-[10px] font-data mb-1 ' + changeColor + '">'
                        + priceChange + ' <span class="opacity-70">' + percentChange + '</span>'
                        + '</p>'
                        + '<p class="text-sm font-data font-bold text-white leading-none">' + moneyPlain(stock.price) + '</p>'
                        + '</div>'
                        + '</div>';
                }).join('') || '<div class="px-4 py-5 text-xs text-slate-500 text-center">No symbols found</div>';

                refs.list.querySelectorAll('[data-symbol]').forEach(function (item) {
                    item.addEventListener('click', function () {
                        state.selectedSymbol = item.dataset.symbol;
                        var strats = state.strategies.filter(function (s) { return s.symbol === state.selectedSymbol; });
                        state.selectedStrategyId = strats[0] ? strats[0].id : null;
                        render();
                        fetchSymbolData(state.selectedSymbol);
                    });
                });
            }

            function renderHero() {
                var stock = selectedStock();
                var priceDiff;
                var percentDiff;
                if (!stock) { return; }
                priceDiff = Number(stock.price || 0) - Number(stock.basePrice || 0);
                percentDiff = Number(stock.basePrice || 0) > 0
                    ? (priceDiff * 100) / Number(stock.basePrice || 0)
                    : Number(stock.change || 0);
                refs.heroSymbol.textContent = stock.symbol;
                refs.heroName.textContent = stock.displayName + ' live trading workspace';
                refs.heroPrice.textContent = moneyPlain(stock.price);
                refs.heroChange.textContent = pct(percentDiff);
                refs.heroChange.className = 'text-base font-data font-bold leading-none ' + (priceDiff >= 0 ? 'text-emerald-400' : 'text-rose-400');
                refs.heroTime.textContent = stock.lastTickAt ? 'Last tick: ' + new Date(stock.lastTickAt).toLocaleTimeString() : 'Prev Close: ' + money(stock.basePrice);
                refs.selectedChip.textContent = stock.symbol;
                refs.footerSymbol.textContent = stock.symbol;
            }

            function renderMetrics() {
                var currentPrice = selectedStock() ? selectedStock().price : 0;
                var strategies = selectedStrategies();
                var open = strategies.reduce(function (sum, s) { return sum + metrics(s, currentPrice).openPnl; }, 0);
                var closed = strategies.reduce(function (sum, s) { return sum + s.closedPnl; }, 0);

                refs.metricStrategies.textContent = String(strategies.length);
                refs.metricOpen.textContent = money(open);
                refs.metricClosed.textContent = money(closed);
                refs.metricOpen.className = 'font-data font-bold text-sm ' + (open >= 0 ? 'text-emerald-400' : 'text-rose-400');
                refs.metricClosed.className = 'font-data font-bold text-sm ' + (closed >= 0 ? 'text-emerald-400' : 'text-rose-400');
            }

            function renderLadder() {
                var strategy = selectedStrategy();
                var currentPrice, lm;
                var remoteLadder = state.lotLadder;
                var remoteRows;
                var header;
                var priceMarkerInserted;
                var rows;

                if (remoteLadder.loading && remoteLadder.symbol === state.selectedSymbol) {
                    refs.ladderSummary.textContent = 'Loading...';
                    refs.lotLadder.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-rotate"></i><p><strong>Loading Lot Ladder</strong>Fetching active GTT orders, open orders, and positions</p></div>';
                    return;
                }

                if (remoteLadder.error && remoteLadder.symbol === state.selectedSymbol) {
                    refs.ladderSummary.textContent = 'Lot ladder unavailable';
                    refs.lotLadder.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-triangle-exclamation"></i><p><strong>Unable to Load Ladder</strong>' + esc(remoteLadder.error) + '</p></div>';
                    return;
                }

                remoteRows = remoteLadder.symbol === state.selectedSymbol ? remoteLadder.rows : [];

                if (remoteRows && remoteRows.length) {
                    currentPrice = (selectedStock() || {}).price || 0;
                    refs.ladderSummary.textContent = remoteLadder.summary || 'Active strategy';

                    header = ''
                        + '<div class="grid grid-cols-[40px_1fr_1fr_52px_68px_88px] gap-2 px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-slate-600 border-b border-t-border mb-1">'
                        + '<span>Lvl</span><span>Buy → Target</span><span></span><span class="text-right">Qty</span><span class="text-right">Status</span><span class="text-right">P&amp;L</span>'
                        + '</div>';

                    priceMarkerInserted = false;
                    rows = [];

                    remoteRows.forEach(function (level) {
                        var status = String(level.status || 'PENDING').toUpperCase();
                        var isHeld = status === 'HELD';
                        var isOpen = status === 'OPEN';
                        var pnl = Number(level.pnl || 0);
                        var pnlClass = pnl >= 0 ? 'pnl-pos' : 'pnl-neg';
                        var statusColor = isHeld
                            ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30'
                            : (isOpen
                                ? 'bg-sky-500/15 text-sky-300 border border-sky-500/30'
                                : 'bg-amber-500/15 text-amber-300 border border-amber-500/30');
                        var rowClass = 'ladder-row grid grid-cols-[40px_1fr_1fr_52px_68px_88px] gap-2 items-center px-3 py-2 rounded-xl text-sm bg-t-raised/45';
                        var buyCell;

                        if (!priceMarkerInserted && currentPrice >= Number(level.buy_price || 0)) {
                            priceMarkerInserted = true;
                            rows.push(
                                '<div class="price-marker">'
                                + '<span class="price-marker-label">CMP</span>'
                                + '<span class="price-marker-line"></span>'
                                + '<span class="price-marker-value">' + moneyPlain(currentPrice) + '</span>'
                                + '</div>'
                            );
                        }

                        buyCell = '<div><span class="font-data font-bold text-emerald-400">' + moneyPlain(level.buy_price) + '</span>';
                        if (isHeld) {
                            var range = Number(level.target_price || 0) - Number(level.buy_price || 0);
                            var progress = range > 0 ? Math.max(0, Math.min(100, ((currentPrice - Number(level.buy_price || 0)) / range) * 100)) : 0;
                            buyCell += '<div class="target-bar"><div class="target-bar-inner" style="width:' + progress.toFixed(0) + '%"></div></div>';
                        } else if (isOpen) {
                            buyCell += '<div><span class="dist-chip near">Exchange open</span></div>';
                        } else {
                            var dist = currentPrice - Number(level.buy_price || 0);
                            var distClass = dist < 1 ? 'dist-chip very-near' : dist < 3 ? 'dist-chip near' : 'dist-chip';
                            buyCell += '<div><span class="' + distClass + '">' + moneyPlain(Math.abs(dist)) + '</span></div>';
                        }
                        buyCell += '</div>';

                        rows.push(''
                            + '<div class="' + rowClass + '">'
                            + '<span class="font-data font-bold text-slate-500">' + esc(level.level) + '</span>'
                            + buyCell
                            + '<span class="font-data font-bold text-amber-300">' + moneyPlain(level.target_price) + '</span>'
                            + '<span class="font-data text-right text-slate-400 font-medium">' + esc(level.quantity) + '</span>'
                            + '<span class="text-right"><span class="text-[9px] font-bold rounded px-1.5 py-0.5 ' + statusColor + '">' + esc(status) + '</span></span>'
                            + '<span class="font-data text-right font-bold text-sm ' + pnlClass + '">' + moneyPlain(pnl) + '</span>'
                            + '</div>'
                        );
                    });

                    if (!priceMarkerInserted) {
                        rows.push(
                            '<div class="price-marker">'
                            + '<span class="price-marker-label">CMP</span>'
                            + '<span class="price-marker-line"></span>'
                            + '<span class="price-marker-value">' + moneyPlain(currentPrice) + '</span>'
                            + '</div>'
                        );
                    }

                    refs.lotLadder.innerHTML = header + rows.join('');
                    return;
                }

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
                            + '<span class="price-marker-value">' + moneyPlain(currentPrice) + '</span>'
                            + '</div>'
                        );
                    }

                    var pnl = level.status === 'held' ? (currentPrice - level.buyPrice) * level.qty : level.closedPnl;
                    var pnlClass = pnl >= 0 ? 'pnl-pos' : 'pnl-neg';
                    var statusColor = level.status === 'held'
                        ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30'
                        : 'bg-amber-500/15 text-amber-300 border border-amber-500/30';
                    var statusText = level.status === 'held' ? 'HELD' : 'PENDING';
                    var rowClass = 'ladder-row is-' + level.status + ' grid grid-cols-[40px_1fr_1fr_52px_68px_88px] gap-2 items-center px-3 py-2 rounded-xl text-sm bg-t-raised/45';

                    var buyCell = '<div><span class="font-data font-bold text-emerald-400">' + moneyPlain(level.buyPrice) + '</span>';
                    if (level.status === 'held') {
                        var range = level.targetPrice - level.buyPrice;
                        var progress = range > 0 ? Math.max(0, Math.min(100, ((currentPrice - level.buyPrice) / range) * 100)) : 0;
                        buyCell += '<div class="target-bar"><div class="target-bar-inner" style="width:' + progress.toFixed(0) + '%"></div></div>';
                    } else {
                        var dist = currentPrice - level.buyPrice;
                        var distClass = dist < strategy.buyOffset * 0.75 ? 'dist-chip very-near' : dist < strategy.buyOffset * 1.5 ? 'dist-chip near' : 'dist-chip';
                        buyCell += '<div><span class="' + distClass + '">-' + moneyPlain(dist) + '</span></div>';
                    }
                    buyCell += '</div>';

                    rows.push(''
                        + '<div class="' + rowClass + '">'
                        + '<span class="font-data font-bold text-slate-500">' + level.level + '</span>'
                        + buyCell
                        + '<span class="font-data font-bold text-amber-300">' + moneyPlain(level.targetPrice) + '</span>'
                        + '<span class="font-data text-right text-slate-400 font-medium">' + level.qty + '</span>'
                        + '<span class="text-right"><span class="text-[9px] font-bold rounded px-1.5 py-0.5 ' + statusColor + '">' + statusText + '</span></span>'
                        + '<span class="font-data text-right font-bold text-sm ' + pnlClass + '">' + moneyPlain(pnl) + '</span>'
                        + '</div>'
                    );
                });

                if (!priceMarkerInserted) {
                    rows.push(
                        '<div class="price-marker">'
                        + '<span class="price-marker-label">CMP</span>'
                        + '<span class="price-marker-line"></span>'
                        + '<span class="price-marker-value">' + moneyPlain(currentPrice) + '</span>'
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
                    var sm = metrics(strategy, currentPrice);
                    var total = strategy.closedPnl + sm.openPnl;
                    var bar = strategy.capitalLimit ? Math.min((sm.committed / strategy.capitalLimit) * 100, 100) : 0;
                    var isSelected = strategy.id === state.selectedStrategyId;
                    return ''
                        + '<div data-strategy="' + strategy.id + '" class="strategy-card rounded-2xl border p-4 ' + (isSelected ? 'selected border-blue-400/50 ' : 'border-t-border bg-t-raised/50') + '">'
                        + '<div class="flex items-start justify-between mb-3"><div><p class="text-sm font-bold text-white">' + esc(strategy.name) + '</p><p class="text-xs text-slate-500 mt-1">Buy INR ' + strategy.buyOffset + ' | Sell INR ' + strategy.sellOffset + ' | Lot ' + strategy.lotSize + '</p></div>'
                        + '<div class="text-right"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Total P&amp;L</p><p class="text-base font-data font-bold ' + (total >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(total) + '</p></div></div>'
                        + '<div class="grid grid-cols-4 gap-2 mb-3">'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Base</p><p class="text-sm font-data font-bold text-white">' + money(strategy.basePrice) + '</p></div>'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Held / Pend</p><p class="text-sm font-data font-bold text-white">' + sm.held + ' / ' + sm.pending + '</p></div>'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Committed</p><p class="text-sm font-data font-bold text-white">' + money(sm.committed) + '</p></div>'
                        + '<div class="rounded-xl bg-t-base/65 border border-t-border px-2.5 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Remaining</p><p class="text-sm font-data font-bold text-white">' + money(sm.remaining) + '</p></div>'
                        + '</div><div class="flex items-center gap-2.5"><div class="flex-1 h-2 rounded-full bg-t-base overflow-hidden border border-t-border/60"><div class="capital-bar h-full rounded-full ' + (bar > 80 ? 'bg-rose-400' : bar > 50 ? 'bg-amber-400' : 'bg-blue-400') + '" style="width:' + bar + '%"></div></div><span class="text-xs font-data font-semibold text-slate-400 shrink-0">' + bar.toFixed(0) + '%</span></div></div>';
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
                var livePositions = state.positions.symbol === state.selectedSymbol ? state.positions.rows : [];
                var totalPnl = livePositions.reduce(function (sum, position) { return sum + Number(position.pnl || 0); }, 0);

                refs.openTradeCount.textContent = String(livePositions.length);

                if (state.positions.loading && state.positions.symbol === state.selectedSymbol) {
                    refs.openTrades.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-rotate"></i><p><strong>Loading Positions</strong>Fetching live Zerodha positions for the selected symbol</p></div>';
                    return;
                }

                if (state.positions.error && state.positions.symbol === state.selectedSymbol) {
                    refs.openTrades.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-triangle-exclamation"></i><p><strong>Unable to Load Positions</strong>' + esc(state.positions.error) + '</p></div>';
                    return;
                }

                if (!livePositions.length) {
                    refs.openTrades.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-briefcase"></i><p><strong>No Open Positions</strong>No live Zerodha positions found for this selected symbol</p></div>';
                    return;
                }

                refs.openTrades.innerHTML = ''
                    + '<div class="flex items-center justify-between rounded-xl bg-emerald-500/8 border border-emerald-500/15 px-3 py-2 mb-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><span class="text-[10px] text-slate-400">Total Unrealized P&amp;L</span><span class="text-sm font-data font-bold ' + (totalPnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(totalPnl) + '</span></div>'
                    + '<div class="overflow-x-auto"><table class="w-full text-[11px]"><thead><tr class="border-b border-t-border text-[9px] uppercase tracking-wider text-slate-500"><th class="pb-2 pr-3 text-left">Symbol</th><th class="pb-2 pr-3 text-left">Product</th><th class="pb-2 pr-3 text-right font-data">Avg Buy</th><th class="pb-2 pr-3 text-right font-data">Last Price</th><th class="pb-2 pr-3 text-right font-data">Buy Qty</th><th class="pb-2 pr-3 text-right font-data">Sell Qty</th><th class="pb-2 pr-3 text-right font-data">Net Qty</th><th class="pb-2 text-right font-data">P&amp;L</th></tr></thead><tbody>'
                    + livePositions.map(function (position) {
                        return '<tr class="row-hover border-b border-t-border/50"><td class="py-2 pr-3 font-semibold text-white">' + esc(position.symbol) + '</td><td class="py-2 pr-3 text-slate-400">' + esc(position.product) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + money(position.average_price) + '</td><td class="py-2 pr-3 text-right font-data font-medium text-white">' + money(position.last_price) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + esc(position.buy_quantity) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + esc(position.sell_quantity) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + esc(position.quantity) + '</td><td class="py-2 text-right font-data font-semibold ' + (Number(position.pnl || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(position.pnl) + '</td></tr>';
                    }).join('')
                    + '</tbody></table></div>';
            }

            function renderTrades() {
                var closedTrades = state.closedTrades.symbol === state.selectedSymbol ? state.closedTrades.rows : [];
                var totalPnl = closedTrades.reduce(function (sum, trade) { return sum + Number(trade.pnl || 0); }, 0);

                refs.tradeCount.textContent = String(closedTrades.length);

                if (state.closedTrades.loading && state.closedTrades.symbol === state.selectedSymbol) {
                    refs.tradeLog.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-rotate"></i><p><strong>Loading Closed Trades</strong>Fetching completed strategy levels for the selected symbol</p></div>';
                    return;
                }

                if (state.closedTrades.error && state.closedTrades.symbol === state.selectedSymbol) {
                    refs.tradeLog.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-triangle-exclamation"></i><p><strong>Unable to Load Closed Trades</strong>' + esc(state.closedTrades.error) + '</p></div>';
                    return;
                }

                if (!closedTrades.length) {
                    refs.tradeLog.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-check-circle"></i><p><strong>No Closed Trades</strong>No closed strategy levels found for this selected symbol</p></div>';
                    return;
                }

                refs.tradeLog.innerHTML = ''
                    + '<div class="flex items-center justify-between rounded-xl bg-sky-500/8 border border-sky-500/15 px-3 py-2 mb-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"><span class="text-[10px] text-slate-400">Total Realized P&amp;L</span><span class="text-sm font-data font-bold ' + (totalPnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(totalPnl) + '</span></div>'
                    + '<div class="overflow-x-auto"><table class="w-full text-[11px]"><thead><tr class="border-b border-t-border text-[9px] uppercase tracking-wider text-slate-500"><th class="pb-2 pr-3 text-left">Symbol</th><th class="pb-2 pr-3 text-left">Level</th><th class="pb-2 pr-3 text-right font-data">Buy</th><th class="pb-2 pr-3 text-right font-data">Sell</th><th class="pb-2 pr-3 text-right font-data">Qty</th><th class="pb-2 pr-3 text-left">Closed At</th><th class="pb-2 text-right font-data">P&amp;L</th></tr></thead><tbody>'
                    + closedTrades.map(function (trade) {
                        var closedAt = trade.closed_at ? new Date(trade.closed_at).toLocaleString() : '--';
                        return '<tr class="row-hover border-b border-t-border/50"><td class="py-2 pr-3 font-semibold text-white">' + esc(trade.symbol) + '</td><td class="py-2 pr-3 text-slate-400">L' + esc(trade.level) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + money(trade.buy_price) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + money(trade.sell_price) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + esc(trade.quantity) + '</td><td class="py-2 pr-3 text-slate-400">' + esc(closedAt) + '</td><td class="py-2 text-right font-data font-semibold ' + (Number(trade.pnl || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(trade.pnl) + '</td></tr>';
                    }).join('')
                    + '</tbody></table></div>';
            }

            function renderAllHistory() {
                var history = [];
                state.strategies.filter(function (s) { return s.symbol === state.selectedSymbol; }).forEach(function (strategy) {
                    strategy.levels.filter(function (l) { return l.status === 'held'; }).forEach(function (level) {
                        var stock = state.stocks.find(function (s) { return s.symbol === strategy.symbol; });
                        var currentPrice = stock ? stock.price : strategy.basePrice;
                        history.push({ type: 'OPEN', strategyName: strategy.name, symbol: strategy.symbol, level: level.level, buyPrice: level.buyPrice, exitPrice: currentPrice, qty: level.qty, pnl: (currentPrice - level.buyPrice) * level.qty, time: null });
                    });
                });
                state.trades.filter(function (t) { return t.symbol === state.selectedSymbol; }).forEach(function (trade) {
                    history.push({ type: 'CLOSED', strategyName: trade.strategyName, symbol: trade.symbol, level: trade.level, buyPrice: trade.buyPrice, exitPrice: trade.sellPrice, qty: trade.qty, pnl: trade.pnl, time: trade.closedAt });
                });
                history.sort(function (a, b) { if (a.type === 'OPEN' && b.type !== 'OPEN') return -1; if (a.type !== 'OPEN' && b.type === 'OPEN') return 1; if (a.time && b.time) return b.time - a.time; return 0; });
                refs.allHistoryCount.textContent = String(history.length);
                if (!history.length) {
                    refs.allHistory.innerHTML = '<div class="empty-state-box"><i class="fa-solid fa-clock-rotate-left"></i><p><strong>No Trade History</strong>All open and closed positions appear here</p></div>';
                    return;
                }
                refs.allHistory.innerHTML = '<div class="overflow-x-auto"><table class="w-full text-[11px]"><thead><tr class="border-b border-t-border text-[9px] uppercase tracking-wider text-slate-500"><th class="pb-2 pr-3 text-left">Status</th><th class="pb-2 pr-3 text-left">Symbol</th><th class="pb-2 pr-3 text-left">Strategy</th><th class="pb-2 pr-3 text-right font-data">Buy</th><th class="pb-2 pr-3 text-right font-data">Exit</th><th class="pb-2 pr-3 text-right font-data">Qty</th><th class="pb-2 text-right font-data">P&amp;L</th></tr></thead><tbody>'
                    + history.map(function (h) {
                        var statusBadge = h.type === 'OPEN' ? '<span class="text-[9px] rounded-md px-1.5 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">OPEN</span>' : '<span class="text-[9px] rounded-md px-1.5 py-0.5 bg-sky-500/10 border border-sky-500/20 text-sky-300 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">CLOSED</span>';
                        return '<tr class="row-hover border-b border-t-border/50"><td class="py-2 pr-3">' + statusBadge + '</td><td class="py-2 pr-3 font-semibold text-white">' + esc(h.symbol) + '</td><td class="py-2 pr-3 text-slate-400">' + esc(h.strategyName) + ' &middot;L' + h.level + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + money(h.buyPrice) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + money(h.exitPrice) + '</td><td class="py-2 pr-3 text-right font-data text-slate-300">' + h.qty + '</td><td class="py-2 text-right font-data font-semibold ' + (h.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400') + '">' + money(h.pnl) + '</td></tr>';
                    }).join('')
                    + '</tbody></table></div>';
            }

            function renderCreateButton() {
                var active = selectedStrategies().length > 0;
                refs.create.disabled = active;
                refs.create.className = active ? 'flex-1 rounded-xl px-4 py-2.5 text-sm font-bold cursor-not-allowed bg-slate-800/90 text-slate-500 border border-t-border' : 'primary-btn flex-1 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors';
                refs.create.textContent = active ? 'Strategy Already Running' : 'Create Strategy';
            }

            function render() {
                renderCreateButton();
                renderList();
                renderHero();
                renderMetrics();
                renderLadder();
                renderStrategies();
                renderOpenTrades();
                renderTrades();
                renderAllHistory();
            }

            function createLocalStrategy(stock, settings, remoteStrategy) {
                var strategy = {
                    id: Date.now(), name: stock.symbol, symbol: stock.symbol, basePrice: stock.price,
                    buyOffset: settings.buyOffset, sellOffset: settings.sellOffset, lotSize: settings.lotSize,
                    lotsLimit: settings.lotsLimit, capitalLimit: settings.capitalLimit, closedPnl: 0,
                    recycleCount: settings.lotsLimit + 1, topBuyPrice: stock.price,
                    remote: remoteStrategy || null,
                    levels: levels(stock.price, settings).map(function (level, index) {
                        return Object.assign({}, level, { qty: settings.lotSize, status: index === 0 ? 'held' : 'pending', closedPnl: 0 });
                    })
                };

                state.strategies.push(strategy);
                state.selectedStrategyId = strategy.id;
                render();
            }

            async function createStrategy() {
                if (selectedStrategies().length > 0) { return; }
                var stock = selectedStock();
                var settings = form();
                var response;
                var result;
                if (!stock || Object.values(settings).some(function (v) { return !Number.isFinite(v) || v <= 0; })) { return; }
                refs.create.disabled = true;
                refs.create.textContent = 'Creating...';

                try {
                    response = await window.fetch(createStrategyUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            symbol: stock.symbol,
                            base_price: stock.price,
                            buy_offset: settings.buyOffset,
                            sell_offset: settings.sellOffset,
                            lot_size: settings.lotSize,
                            lots_limit: settings.lotsLimit,
                            capital_limit: settings.capitalLimit
                        })
                    });

                    result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to create live strategy.');
                    }

                    createLocalStrategy(stock, settings, result.strategy || null);
                    await fetchSymbolData(stock.symbol);
                    window.alert(result.message || 'Live strategy created successfully.');
                } catch (error) {
                    window.alert(error && error.message ? error.message : 'Unable to create live strategy.');
                } finally {
                    renderCreateButton();
                }
            }

            function runSimulation(symbol, livePrice) {
                state.strategies.filter(function (strategy) { return strategy.symbol === symbol; }).forEach(function (strategy) {
                    strategy.levels.forEach(function (level) {
                        var committed;
                        if (level.status === 'pending' && livePrice <= level.buyPrice) {
                            committed = strategy.levels.filter(function (item) { return item.status !== 'closed'; }).reduce(function (sum, item) { return sum + (item.buyPrice * item.qty); }, 0);
                            if (committed + (level.buyPrice * level.qty) <= strategy.capitalLimit) {
                                level.status = 'held';
                            }
                        }
                        if (level.status === 'held' && livePrice >= level.targetPrice) {
                            level.status = 'closed';
                            level.closedPnl = (level.targetPrice - level.buyPrice) * level.qty;
                            strategy.closedPnl += level.closedPnl;
                            state.trades.push({ strategyId: strategy.id, strategyName: strategy.name, symbol: symbol, level: level.level, qty: level.qty, buyPrice: level.buyPrice, sellPrice: level.targetPrice, pnl: level.closedPnl, closedAt: Date.now() });
                            recycleLevel(strategy, level);
                        }
                    });
                });
            }
            function handleLiveTicks(ticks) {
                state.lastTickAt = Date.now();
                ticks.forEach(function (tick) {
                    var stock = state.stocks.find(function (item) { return item.token === tick.instrument_token; });
                    if (!stock) { return; }
                    stock.price = tick.last_price;
                    stock.change = tick.change;
                    stock.lastTickAt = Date.now();
                    runSimulation(stock.symbol, tick.last_price);
                });
                refreshHeartbeat();
                scheduleRender();
            }

            function initializeLivePriceTracking() {
                var tokens;
                if (!apiKey || !accessToken) {
                    setStatus('text-amber-400', 'Not Configured');
                    refs.lastUpdated.textContent = 'Add Kite credentials in .env';
                    refs.statusTime.textContent = 'Add Kite credentials in .env';
                    return;
                }
                if (typeof window.createKiteLivePriceSocket !== 'function') {
                    setStatus('text-rose-400', 'Socket Script Missing');
                    refs.lastUpdated.textContent = 'Live price WebSocket script was not loaded';
                    refs.statusTime.textContent = 'Live price WebSocket script was not loaded';
                    return;
                }
                tokens = state.stocks
                    .map(function (stock) { return Number(stock.token || 0); })
                    .filter(function (token) { return token > 0; });
                state.livePriceSocket = window.createKiteLivePriceSocket({
                    apiKey: apiKey,
                    accessToken: accessToken,
                    tokens: tokens,
                    onStatusChange: setStatus,
                    onTicks: handleLiveTicks,
                    onError: function (error) { console.error('WebSocket Error:', error); },
                    onReconnect: function (delay) {
                        refs.lastUpdated.textContent = 'Retrying in ' + delay + 's';
                        refs.statusTime.textContent = 'Retrying in ' + delay + 's';
                    },
                    onStopped: function () {
                        refs.lastUpdated.textContent = 'Feed stopped';
                        refs.statusTime.textContent = 'Feed stopped';
                    },
                });
                state.livePriceSocket.connect();
            }

            refs.search.addEventListener('input', function () { renderList(); });
            refs.create.addEventListener('click', createStrategy);
            refs.reset.addEventListener('click', function () {
                state.strategies = [];
                state.trades = [];
                state.selectedStrategyId = null;
                render();
            });

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

            window.setInterval(refreshHeartbeat, 1000);
            setStatus('text-slate-500', 'Connecting...');
            refreshHeartbeat();
            render();
            fetchSymbolData(state.selectedSymbol);
            initializeLivePriceTracking();
        };
    })(window);

    window.initializeStocksSimulator({
        stockData: @json($stockData),
        apiKey: @json($apiKey),
        accessToken: @json($accessToken),
        createStrategyUrl: @json(route('create.strategy.store')),
        symbolDataUrl: @json(route('symbol.data')),
        csrfToken: @json(csrf_token()),
    });
    </script>
</body>
</html>
