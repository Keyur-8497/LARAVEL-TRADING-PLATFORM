<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategy Simulator | Zerodha Kite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="border-b border-slate-800 bg-slate-950">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Paper Trading Lab</p>
                <h1 class="text-2xl font-semibold text-white">Strategy Simulator</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5 text-slate-300">
                    <i id="status-dot" class="fa-solid fa-circle mr-2 text-slate-500"></i>
                    <span id="status-text">Connecting...</span>
                </span>
                <span id="last-updated" class="rounded-full border border-slate-700 bg-slate-900 px-3 py-1.5 text-slate-400">Waiting for first tick...</span>
            </div>
        </div>
    </div>

    @if(isset($error))
        <div class="mx-auto mt-4 max-w-7xl px-4">
            <div class="rounded-2xl border border-rose-800/60 bg-rose-950/70 px-4 py-3 text-rose-200">
                <p class="font-semibold">API Connection Error</p>
                <p class="text-sm text-rose-300">{{ $error }}</p>
            </div>
        </div>
    @endif

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-4 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80">
            <div class="border-b border-slate-800 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Scripts</p>
                <input id="symbol-search" type="text" placeholder="Search symbol..." class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-200 placeholder:text-slate-500 focus:border-sky-500 focus:outline-none">
            </div>
            <div id="script-list" class="max-h-[calc(100vh-220px)] overflow-y-auto"></div>
        </aside>

        <main class="grid gap-4">
            <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Selected Script</p>
                        <div class="mt-2 flex flex-wrap items-end gap-4">
                            <div>
                                <h2 id="hero-symbol" class="text-4xl font-semibold text-white">--</h2>
                                <p id="hero-name" class="mt-1 text-sm text-slate-400">Waiting for market data</p>
                            </div>
                            <div class="rounded-2xl bg-emerald-500/10 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">CMP</p>
                                <p id="hero-price" class="mt-1 text-4xl font-semibold text-emerald-400">Rs 0.00</p>
                            </div>
                            <div class="pb-1">
                                <p id="hero-change" class="text-lg font-medium text-slate-300">0.00%</p>
                                <p id="hero-time" class="text-sm text-slate-500">No ticks yet</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Strategies</p>
                            <p id="metric-strategies" class="mt-2 text-2xl font-semibold text-white">0</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Open P&amp;L</p>
                            <p id="metric-open" class="mt-2 text-2xl font-semibold text-white">Rs 0.00</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Closed P&amp;L</p>
                            <p id="metric-closed" class="mt-2 text-2xl font-semibold text-white">Rs 0.00</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Events</p>
                            <p id="metric-events" class="mt-2 text-2xl font-semibold text-white">0</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Create Strategy</p>
                            <h3 class="mt-1 text-xl font-semibold text-white">Buy On Dips, Sell On Rise</h3>
                        </div>
                        <span id="selected-script-chip" class="rounded-full bg-sky-500/10 px-3 py-1 text-sm text-sky-300">--</span>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <label class="block">
                            <span class="mb-2 block text-sm text-slate-300">Strategy Name</span>
                            <input id="strategy-name" type="text" value="Auto Ladder" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 focus:border-sky-500 focus:outline-none">
                        </label>
                        <label class="block"><span class="mb-2 block text-sm text-slate-300">Buy Offset (Rs)</span><input id="buy-offset" type="number" min="0.01" step="0.01" value="5" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 focus:border-sky-500 focus:outline-none"></label>
                        <label class="block"><span class="mb-2 block text-sm text-slate-300">Sell Offset (Rs)</span><input id="sell-offset" type="number" min="0.01" step="0.01" value="10" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 focus:border-sky-500 focus:outline-none"></label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="mb-2 block text-sm text-slate-300">Lot Size</span><input id="lot-size" type="number" min="1" step="1" value="25" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 focus:border-sky-500 focus:outline-none"></label>
                            <label class="block"><span class="mb-2 block text-sm text-slate-300">Lots Limit</span><input id="lots-limit" type="number" min="1" step="1" value="5" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 focus:border-sky-500 focus:outline-none"></label>
                        </div>
                        <label class="block"><span class="mb-2 block text-sm text-slate-300">Price Limit (Rs)</span><input id="capital-limit" type="number" min="1" step="1" value="500000" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 focus:border-sky-500 focus:outline-none"></label>
                    </div>

                    <div id="strategy-preview" class="mt-5 rounded-3xl border border-slate-800 bg-slate-950/80 p-4"></div>
                    <div class="mt-4 flex gap-3">
                        <button id="create-strategy" class="flex-1 rounded-2xl bg-sky-500 px-4 py-3 font-medium text-slate-950 hover:bg-sky-400">Create Strategy</button>
                        <button id="reset-strategies" class="rounded-2xl border border-slate-700 px-4 py-3 font-medium text-slate-300 hover:border-rose-500 hover:text-rose-300">Reset Demo</button>
                    </div>
                </div>

                <div class="grid gap-4">
                    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Lot Ladder</p>
                                <h3 class="mt-1 text-xl font-semibold text-white">Selected Strategy Levels</h3>
                            </div>
                            <span id="ladder-summary" class="rounded-full bg-emerald-500/10 px-3 py-1 text-sm text-emerald-300">No strategy selected</span>
                        </div>
                        <div id="lot-ladder" class="mt-4 space-y-3"></div>
                    </section>

                    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Active Strategies</p>
                            <h3 class="mt-1 text-xl font-semibold text-white">Running Simulations</h3>
                        </div>
                        <div id="strategy-table" class="mt-4 grid gap-4"></div>
                    </section>

                    <section class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Trade Log</p>
                                    <h3 class="mt-1 text-xl font-semibold text-white">Closed Trades</h3>
                                </div>
                                <span id="trade-count" class="rounded-full bg-slate-800 px-3 py-1 text-sm text-slate-300">0 trades</span>
                            </div>
                            <div id="trade-log" class="mt-4 space-y-3"></div>
                        </div>

                        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Event Log</p>
                                    <h3 class="mt-1 text-xl font-semibold text-white">Buy / Sell Activity</h3>
                                </div>
                                <span id="event-count" class="rounded-full bg-slate-800 px-3 py-1 text-sm text-slate-300">0 events</span>
                            </div>
                            <div id="event-log" class="mt-4 space-y-3"></div>
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <script>
        (function () {
            const initialStocks = @json($stockData);
            const apiKey = @json(config('kite.api_key'));
            const accessToken = @json(config('kite.access_token'));
            const $ = (id) => document.getElementById(id);
            const refs = { list: $('script-list'), search: $('symbol-search'), heroSymbol: $('hero-symbol'), heroName: $('hero-name'), heroPrice: $('hero-price'), heroChange: $('hero-change'), heroTime: $('hero-time'), statusDot: $('status-dot'), statusText: $('status-text'), lastUpdated: $('last-updated'), metricStrategies: $('metric-strategies'), metricOpen: $('metric-open'), metricClosed: $('metric-closed'), metricEvents: $('metric-events'), strategyName: $('strategy-name'), buyOffset: $('buy-offset'), sellOffset: $('sell-offset'), lotSize: $('lot-size'), lotsLimit: $('lots-limit'), capitalLimit: $('capital-limit'), preview: $('strategy-preview'), selectedChip: $('selected-script-chip'), ladderSummary: $('ladder-summary'), lotLadder: $('lot-ladder'), strategyTable: $('strategy-table'), tradeLog: $('trade-log'), tradeCount: $('trade-count'), eventLog: $('event-log'), eventCount: $('event-count'), create: $('create-strategy'), reset: $('reset-strategies') };
            const state = { stocks: initialStocks.map((stock) => ({ ...stock, displayName: stock.symbol, lastTickAt: null })), selectedSymbol: initialStocks[0]?.symbol ?? null, selectedStrategyId: null, strategies: [], trades: [], events: [], socket: null, reconnectCount: 0, lastTickAt: null };
            const money = (value) => `Rs ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const pct = (value) => `${value >= 0 ? '+' : '-'}${Math.abs(Number(value || 0)).toFixed(2)}%`;
            const selectedStock = () => state.stocks.find((s) => s.symbol === state.selectedSymbol) ?? state.stocks[0] ?? null;
            const selectedStrategies = () => state.strategies.filter((s) => s.symbol === state.selectedSymbol);
            const selectedStrategy = () => state.strategies.find((s) => s.id === state.selectedStrategyId) ?? selectedStrategies()[0] ?? null;
            const form = () => ({ name: refs.strategyName.value.trim() || 'Auto Ladder', buyOffset: Number(refs.buyOffset.value), sellOffset: Number(refs.sellOffset.value), lotSize: Number(refs.lotSize.value), lotsLimit: Number(refs.lotsLimit.value), capitalLimit: Number(refs.capitalLimit.value) });

            function setStatus(color, text) { refs.statusDot.className = `fa-solid fa-circle mr-2 ${color}`; refs.statusText.textContent = text; }
            function levels(price, settings) { return Array.from({ length: settings.lotsLimit }, (_, index) => { const buyPrice = Number((price - (index * settings.buyOffset)).toFixed(2)); return { level: index + 1, buyPrice, targetPrice: Number((buyPrice + settings.sellOffset).toFixed(2)) }; }); }
            function metrics(strategy, currentPrice) { const held = strategy.levels.filter((l) => l.status === 'held'); const pending = strategy.levels.filter((l) => l.status === 'pending'); const committed = strategy.levels.filter((l) => l.status !== 'closed').reduce((sum, l) => sum + (l.buyPrice * l.qty), 0); return { held: held.length, pending: pending.length, committed, remaining: Math.max(strategy.capitalLimit - committed, 0), openPnl: held.reduce((sum, l) => sum + ((currentPrice - l.buyPrice) * l.qty), 0) }; }
            function pushEvent(type, strategy, level, livePrice, text) { state.events.push({ id: Date.now() + Math.random(), type, strategyId: strategy.id, symbol: strategy.symbol, level: level.level, livePrice, text, at: Date.now() }); }
            function nextPendingBuy(strategy) { const pending = strategy.levels.filter((level) => level.status === 'pending').map((level) => level.buyPrice); const minPending = pending.length ? Math.min(...pending) : strategy.basePrice; return Number((minPending - strategy.buyOffset).toFixed(2)); }

            function renderList() {
                const query = refs.search.value.trim().toLowerCase();
                const stocks = state.stocks.filter((s) => s.symbol.toLowerCase().includes(query));
                refs.list.innerHTML = stocks.map((stock) => `<button data-symbol="${stock.symbol}" class="flex w-full items-center justify-between border-b border-slate-800 px-4 py-4 text-left ${stock.symbol === state.selectedSymbol ? 'bg-sky-500/10' : 'hover:bg-slate-800/60'}"><div><p class="font-semibold text-white">${stock.symbol}</p><p class="mt-1 text-sm text-slate-400">${stock.displayName}</p></div><div class="text-right"><p class="font-semibold text-white">${money(stock.price)}</p><p class="mt-1 text-sm ${stock.change >= 0 ? 'text-emerald-400' : 'text-rose-400'}">${pct(stock.change)}</p></div></button>`).join('') || '<div class="px-4 py-6 text-sm text-slate-500">No symbols found.</div>';
                refs.list.querySelectorAll('[data-symbol]').forEach((button) => button.addEventListener('click', () => { state.selectedSymbol = button.dataset.symbol; state.selectedStrategyId = selectedStrategies()[0]?.id ?? null; render(); }));
            }

            function renderHero() {
                const stock = selectedStock(); if (!stock) return;
                refs.heroSymbol.textContent = stock.symbol; refs.heroName.textContent = `${stock.displayName} live paper trading workspace`; refs.heroPrice.textContent = money(stock.price); refs.heroChange.textContent = `${pct(stock.change)} from previous close`; refs.heroChange.className = `text-lg font-medium ${stock.change >= 0 ? 'text-emerald-400' : 'text-rose-400'}`; refs.heroTime.textContent = stock.lastTickAt ? `Last tick ${new Date(stock.lastTickAt).toLocaleTimeString()}` : 'No ticks yet'; refs.selectedChip.textContent = stock.symbol;
            }

            function renderPreview() {
                const stock = selectedStock(); const settings = form();
                if (!stock || Object.values(settings).slice(1).some((v) => !Number.isFinite(v) || v <= 0)) { refs.preview.innerHTML = '<p class="text-sm text-slate-500">Enter valid values to preview the ladder.</p>'; return; }
                const ladder = levels(stock.price, settings);
                const capitalNeeded = ladder.reduce((sum, level) => sum + (level.buyPrice * settings.lotSize), 0);
                refs.preview.innerHTML = `<p class="text-xs uppercase tracking-[0.2em] text-slate-500">Strategy Preview</p><div class="mt-3 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-slate-900 px-4 py-3"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Base Buy</p><p class="mt-1 text-lg font-semibold text-emerald-400">${money(ladder[0].buyPrice)}</p></div><div class="rounded-2xl bg-slate-900 px-4 py-3"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Capital Need</p><p class="mt-1 text-lg font-semibold text-amber-300">${money(capitalNeeded)}</p></div></div><div class="mt-3 rounded-2xl bg-slate-900 px-4 py-3 text-sm text-slate-300">${ladder.map((l) => `${money(l.buyPrice)} -> ${money(l.targetPrice)}`).join(' | ')}</div>`;
            }

            function renderMetrics() {
                const currentPrice = selectedStock()?.price ?? 0; const strategies = selectedStrategies(); const open = strategies.reduce((sum, strategy) => sum + metrics(strategy, currentPrice).openPnl, 0); const closed = strategies.reduce((sum, strategy) => sum + strategy.closedPnl, 0);
                refs.metricStrategies.textContent = String(strategies.length); refs.metricOpen.textContent = money(open); refs.metricClosed.textContent = money(closed); refs.metricEvents.textContent = String(state.events.filter((event) => event.symbol === state.selectedSymbol).length); refs.metricOpen.className = `mt-2 text-2xl font-semibold ${open >= 0 ? 'text-emerald-400' : 'text-rose-400'}`; refs.metricClosed.className = `mt-2 text-2xl font-semibold ${closed >= 0 ? 'text-emerald-400' : 'text-rose-400'}`;
            }

            function renderLadder() {
                const strategy = selectedStrategy();
                if (!strategy) { refs.ladderSummary.textContent = 'No strategy selected'; refs.lotLadder.innerHTML = '<div class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500">Create a strategy to see the lot ladder.</div>'; return; }
                const currentPrice = state.stocks.find((s) => s.symbol === strategy.symbol)?.price ?? strategy.basePrice; const m = metrics(strategy, currentPrice); refs.ladderSummary.textContent = `${strategy.name} | ${m.held} held | ${m.pending} pending`;
                refs.lotLadder.innerHTML = strategy.levels.sort((a, b) => b.buyPrice - a.buyPrice).map((level) => { const pnl = level.status === 'held' ? (currentPrice - level.buyPrice) * level.qty : level.closedPnl; const badge = level.status === 'held' ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30' : level.status === 'pending' ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-sky-500/10 text-sky-300 border-sky-500/30'; return `<div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4"><div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"><div><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Level ${level.level}</p><p class="mt-1 text-xl font-semibold text-white">${money(level.buyPrice)}</p></div><div class="grid gap-3 sm:grid-cols-3"><div><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Sell Target</p><p class="mt-1 font-medium text-rose-400">${money(level.targetPrice)}</p></div><div><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Qty</p><p class="mt-1 font-medium text-slate-200">${level.qty}</p></div><div><p class="text-xs uppercase tracking-[0.2em] text-slate-500">P&amp;L</p><p class="mt-1 font-medium ${pnl >= 0 ? 'text-emerald-400' : 'text-rose-400'}">${money(pnl)}</p></div></div><span class="rounded-full border px-3 py-1 text-sm ${badge}">${level.status.toUpperCase()}</span></div></div>`; }).join('');
            }

            function renderStrategies() {
                const strategies = selectedStrategies();
                if (!strategies.length) { refs.strategyTable.innerHTML = '<div class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500">No active strategies for this script yet.</div>'; return; }
                refs.strategyTable.innerHTML = strategies.map((strategy) => { const currentPrice = selectedStock()?.price ?? strategy.basePrice; const m = metrics(strategy, currentPrice); const total = strategy.closedPnl + m.openPnl; const bar = strategy.capitalLimit ? Math.min((m.committed / strategy.capitalLimit) * 100, 100) : 0; return `<button data-strategy="${strategy.id}" class="rounded-3xl border p-4 text-left ${strategy.id === state.selectedStrategyId ? 'border-sky-500 bg-sky-500/10' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'}"><div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"><div><p class="text-xs uppercase tracking-[0.2em] text-slate-500">${strategy.symbol}</p><p class="mt-1 text-xl font-semibold text-white">${strategy.name}</p><p class="mt-1 text-sm text-slate-400">Buy ${strategy.buyOffset} | Sell ${strategy.sellOffset} | Lot ${strategy.lotSize}</p></div><div class="text-left md:text-right"><p class="text-sm text-slate-400">Total P&amp;L</p><p class="mt-1 text-xl font-semibold ${total >= 0 ? 'text-emerald-400' : 'text-rose-400'}">${money(total)}</p></div></div><div class="mt-4 grid gap-3 sm:grid-cols-4"><div class="rounded-2xl bg-slate-900 px-3 py-3"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Base</p><p class="mt-1 font-medium text-white">${money(strategy.basePrice)}</p></div><div class="rounded-2xl bg-slate-900 px-3 py-3"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Held / Pending</p><p class="mt-1 font-medium text-white">${m.held} / ${m.pending}</p></div><div class="rounded-2xl bg-slate-900 px-3 py-3"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Committed</p><p class="mt-1 font-medium text-white">${money(m.committed)}</p></div><div class="rounded-2xl bg-slate-900 px-3 py-3"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Remaining</p><p class="mt-1 font-medium text-white">${money(m.remaining)}</p></div></div><div class="mt-4"><div class="mb-1 flex justify-between text-xs text-slate-500"><span>Capital usage</span><span>${money(m.committed)} / ${money(strategy.capitalLimit)}</span></div><div class="h-2 rounded-full bg-slate-800"><div class="h-2 rounded-full bg-amber-400" style="width:${bar}%"></div></div></div></button>`; }).join('');
                refs.strategyTable.querySelectorAll('[data-strategy]').forEach((row) => row.addEventListener('click', () => { state.selectedStrategyId = Number(row.dataset.strategy); renderLadder(); renderStrategies(); }));
            }

            function renderTrades() {
                const trades = state.trades.filter((trade) => trade.symbol === state.selectedSymbol).slice().reverse(); refs.tradeCount.textContent = `${trades.length} trades`;
                refs.tradeLog.innerHTML = trades.length ? trades.map((trade) => `<div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4"><div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"><div><p class="font-semibold text-white">${trade.symbol} | ${trade.strategyName} | Level ${trade.level}</p><p class="mt-1 text-sm text-slate-400">Buy ${money(trade.buyPrice)} -> Sell ${money(trade.sellPrice)} | Qty ${trade.qty}</p></div><div class="text-right"><p class="${trade.pnl >= 0 ? 'text-emerald-400' : 'text-rose-400'} font-semibold">${money(trade.pnl)}</p><p class="mt-1 text-xs text-slate-500">${new Date(trade.closedAt).toLocaleString()}</p></div></div></div>`).join('') : '<div class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500">No trades executed for this script yet.</div>';
            }

            function renderEvents() {
                const events = state.events.filter((event) => event.symbol === state.selectedSymbol).slice().reverse(); refs.eventCount.textContent = `${events.length} events`;
                refs.eventLog.innerHTML = events.length ? events.map((event) => `<div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold ${event.type === 'BUY' ? 'text-emerald-400' : 'text-amber-300'}">${event.type} � ${event.symbol} � L${event.level}</p><p class="mt-1 text-sm text-slate-400">${event.text}</p></div><div class="text-right"><p class="text-sm text-white">${money(event.livePrice)}</p><p class="mt-1 text-xs text-slate-500">${new Date(event.at).toLocaleTimeString()}</p></div></div></div>`).join('') : '<div class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500">No buy or sell events for this script yet.</div>';
            }

            function render() { renderList(); renderHero(); renderPreview(); renderMetrics(); renderLadder(); renderStrategies(); renderTrades(); renderEvents(); }

            function createStrategy() {
                const stock = selectedStock(); const settings = form(); if (!stock || Object.values(settings).slice(1).some((v) => !Number.isFinite(v) || v <= 0)) return;
                const strategy = { id: Date.now(), name: settings.name, symbol: stock.symbol, basePrice: stock.price, buyOffset: settings.buyOffset, sellOffset: settings.sellOffset, lotSize: settings.lotSize, lotsLimit: settings.lotsLimit, capitalLimit: settings.capitalLimit, closedPnl: 0, recycleCount: settings.lotsLimit + 1, levels: levels(stock.price, settings).map((level, index) => ({ ...level, qty: settings.lotSize, status: index === 0 ? 'held' : 'pending', closedPnl: 0 })) };
                state.strategies.push(strategy); state.selectedStrategyId = strategy.id; pushEvent('BUY', strategy, strategy.levels[0], stock.price, `Base lot activated at ${money(strategy.levels[0].buyPrice)} with target ${money(strategy.levels[0].targetPrice)}`); render();
            }

            function recycleLevel(strategy) {
                const buyPrice = nextPendingBuy(strategy); const targetPrice = Number((buyPrice + strategy.sellOffset).toFixed(2));
                strategy.levels.push({ level: strategy.recycleCount++, buyPrice, targetPrice, qty: strategy.lotSize, status: 'pending', closedPnl: 0 });
            }

            function runSimulation(symbol, livePrice) {
                state.strategies.filter((strategy) => strategy.symbol === symbol).forEach((strategy) => {
                    strategy.levels.forEach((level) => {
                        if (level.status === 'pending' && livePrice <= level.buyPrice) {
                            const committed = strategy.levels.filter((item) => item.status !== 'closed').reduce((sum, item) => sum + (item.buyPrice * item.qty), 0);
                            if (committed + (level.buyPrice * level.qty) <= strategy.capitalLimit) { level.status = 'held'; pushEvent('BUY', strategy, level, livePrice, `Level ${level.level} bought near ${money(livePrice)} and target set to ${money(level.targetPrice)}`); }
                        }
                        if (level.status === 'held' && livePrice >= level.targetPrice) {
                            level.status = 'closed'; level.closedPnl = (level.targetPrice - level.buyPrice) * level.qty; strategy.closedPnl += level.closedPnl;
                            state.trades.push({ strategyId: strategy.id, strategyName: strategy.name, symbol, level: level.level, qty: level.qty, buyPrice: level.buyPrice, sellPrice: level.targetPrice, pnl: level.closedPnl, closedAt: Date.now() });
                            pushEvent('SELL', strategy, level, livePrice, `Level ${level.level} sold at target ${money(level.targetPrice)} for ${money(level.closedPnl)} profit`);
                            recycleLevel(strategy);
                        }
                    });
                });
            }
            function refreshHeartbeat() { if (!state.lastTickAt) { refs.lastUpdated.textContent = 'Waiting for first tick...'; return; } const seconds = Math.max(0, Math.floor((Date.now() - state.lastTickAt) / 1000)); refs.lastUpdated.textContent = seconds === 0 ? 'Updated just now' : `Updated ${seconds}s ago`; }
            function buf2long(buffer) { return Array.from(new Uint8Array(buffer)).reduceRight((value, byte, index, bytes) => value + (byte << ((bytes.length - 1 - index) * 8)), 0); }
            function parseTicks(buffer) { const count = buf2long(buffer.slice(0, 2)); let cursor = 2; const ticks = []; for (let index = 0; index < count; index++) { const size = buf2long(buffer.slice(cursor, cursor + 2)); const packet = buffer.slice(cursor + 2, cursor + 2 + size); cursor += size + 2; const token = buf2long(packet.slice(0, 4)); const segment = token & 0xff; const divisor = segment === 3 ? 10000000 : 100; const lastPrice = buf2long(packet.slice(4, 8)) / divisor; const close = packet.byteLength === 8 ? 0 : buf2long((packet.byteLength === 28 || packet.byteLength === 32) ? packet.slice(20, 24) : packet.slice(40, 44)) / divisor; ticks.push({ instrument_token: token, last_price: lastPrice, change: close ? ((lastPrice - close) * 100) / close : 0 }); } return ticks; }

            function connect() {
                const tokens = state.stocks.map((stock) => stock.token); const url = `wss://ws.kite.trade/?api_key=${apiKey}&access_token=${accessToken}&uid=${Date.now()}`;
                state.socket = new WebSocket(url); state.socket.binaryType = 'arraybuffer';
                state.socket.onopen = function () { state.reconnectCount = 0; setStatus('text-emerald-400', 'Connected'); this.send(JSON.stringify({ a: 'subscribe', v: tokens })); this.send(JSON.stringify({ a: 'mode', v: ['full', tokens] })); };
                state.socket.onmessage = function (event) { if (!(event.data instanceof ArrayBuffer)) return; state.lastTickAt = Date.now(); parseTicks(event.data).forEach((tick) => { const stock = state.stocks.find((item) => item.token === tick.instrument_token); if (!stock) return; stock.price = tick.last_price; stock.change = tick.change; stock.lastTickAt = Date.now(); runSimulation(stock.symbol, tick.last_price); }); refreshHeartbeat(); render(); };
                state.socket.onerror = function (error) { console.error('WebSocket Error:', error); setStatus('text-rose-400', 'Error'); };
                state.socket.onclose = function () { setStatus('text-rose-400', 'Disconnected'); state.reconnectCount += 1; if (state.reconnectCount > 20) { refs.lastUpdated.textContent = 'Feed stopped'; return; } const delay = Math.min(2 ** state.reconnectCount, 30); refs.lastUpdated.textContent = `Retrying in ${delay}s`; setTimeout(connect, delay * 1000); };
            }

            refs.search.addEventListener('input', renderList);
            refs.create.addEventListener('click', createStrategy);
            refs.reset.addEventListener('click', () => { state.strategies = []; state.trades = []; state.events = []; state.selectedStrategyId = null; render(); });
            [refs.strategyName, refs.buyOffset, refs.sellOffset, refs.lotSize, refs.lotsLimit, refs.capitalLimit].forEach((input) => input.addEventListener('input', renderPreview));

            setInterval(refreshHeartbeat, 1000); render();
            if (!apiKey || !accessToken) { setStatus('text-amber-400', 'Not Configured'); refs.lastUpdated.textContent = 'Add Kite credentials in .env'; return; }
            connect();
        })();
    </script>
</body>
</html>

