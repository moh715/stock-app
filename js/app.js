async function fetchQuote(symbol) {
    const res = await fetch(`api/stock.php?action=quote&symbol=${encodeURIComponent(symbol)}`);
    return res.json();
}

async function fetchTimeSeries(symbol) {
    const res = await fetch(`api/stock.php?action=time_series&symbol=${encodeURIComponent(symbol)}`);
    return res.json();
}

async function fetchBatch(stocks) {
    const joined = stocks.map(s => s.symbol).join(',');
    const res    = await fetch(`api/stock.php?action=batch&symbols=${encodeURIComponent(joined)}`);
    const data   = await res.json();

    return stocks.map(s => ({
        symbol:      s.symbol,
        name:        s.name,
        quote:       data[s.symbol]?.quote       ?? { error: 'Not found' },
        time_series: data[s.symbol]?.time_series ?? { error: 'Not found' },
    }));
}


function trendFromQuote(quote) {
    const open  = parseFloat(quote.open  ?? 0);
    const close = parseFloat(quote.close ?? 0);
    return close >= open ? 'up' : 'down';
}

function buildStockCard(symbol, name, quote, timeSeries) {
    const trend    = trendFromQuote(quote);
    const price    = parseFloat(quote.close ?? 0).toFixed(2);
    const badgeClass = trend === 'up' ? 'trend-up' : 'trend-down';
    const badgeText  = trend === 'up' ? '▲ Up'     : '▼ Down';

    const card = document.createElement('div');
    card.className = 'stock-card';
    card.dataset.symbol = symbol;
    card.innerHTML = `
        <div class="stock-card-header">
            <div>
                <div class="stock-card-name">${escHtml(name)}</div>
                <div class="stock-card-symbol">${escHtml(symbol)}</div>
            </div>
            <div class="stock-card-price">
                $${price}
                <br>
                <span class="trend-badge ${badgeClass}">${badgeText}</span>
            </div>
        </div>
        <div style="height:80px">
            <canvas class="stock-chart-small"></canvas>
        </div>
    `;

    card.addEventListener('click', () => {
        window.location.href = `stock.php?symbol=${encodeURIComponent(symbol)}&name=${encodeURIComponent(name)}`;
    });

    const chartData = buildChartData(timeSeries);
    if (chartData) {
        setTimeout(() => {
            const canvas = card.querySelector('canvas');
            if (canvas) createSmallChart(canvas, chartData.labels, chartData.prices);
        }, 0);
    }

    return card;
}


function showSpinner(container) {
    container.innerHTML = '<div class="spinner">Loading stocks</div>';
}

function showEmpty(container, message = 'No stocks found.') {
    container.innerHTML = `
        <div class="empty-state">
            <p>${escHtml(message)}</p>
        </div>
    `;
}

function showError(container, message = 'Something went wrong.') {
    container.innerHTML = `
        <div class="empty-state">
            <div class="icon">⚠️</div>
            <p>${escHtml(message)}</p>
        </div>
    `;
}


async function populateGrid(container, stocks) {
    if (!stocks.length) {
        showEmpty(container);
        return;
    }

    container.innerHTML = '';
    const wrapper = document.createElement('div');
    wrapper.className = 'stock-grid';
    container.appendChild(wrapper);

    let results;
    try {
        results = await fetchBatch(stocks);
    } catch (e) {
        showError(container, 'Failed to load stock data.');
        return;
    }

    let loadedCount = 0;

    for (const entry of results) {
        const { quote, time_series } = entry;
        if (quote?.status === 'error' || quote?.error || !quote?.close) continue;

        const card = buildStockCard(entry.symbol, entry.name, quote, time_series);
        wrapper.appendChild(card);
        loadedCount++;
    }

    if (loadedCount === 0) {
        showEmpty(container, 'Could not load stock data.');
    }
}


function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });
}

function buildNewsCard(article) {
    const card = document.createElement('div');
    card.className = 'news-card';
    card.style.cursor = 'pointer';

    const imageInner = article.urlToImage
        ? `<img class="news-card-image" src="${escHtml(article.urlToImage)}" alt="" loading="lazy" onerror="this.style.display='none'">`
        : `<div class="news-card-image-placeholder">📰</div>`;

    card.innerHTML = `
        <div class="news-card-image-wrap">${imageInner}</div>
        <div class="news-card-body">
            <div class="news-card-title">${escHtml(article.title ?? '')}</div>
            <div class="news-card-meta">
                <span class="news-card-source">${escHtml(article.source?.name ?? '')}</span>
                <span class="news-card-date">${formatDate(article.publishedAt)}</span>
            </div>
        </div>
    `;

    card.addEventListener('click', () => {
        sessionStorage.setItem('currentArticle', JSON.stringify(article));
        window.location.href = 'article.php';
    });

    return card;
}


const aiHistory = [];

function toggleChat() {
    const box = document.getElementById('ai-chat-box');
    box.classList.toggle('open');
}

async function aiSend() {
    const input = document.getElementById('ai-input');
    const text  = input.value.trim().slice(0, 500);
    if (!text) return;

    input.value = '';
    appendAiMessage('user', text);

    aiHistory.push({ role: 'user', parts: [{ text }] });

    const thinking = appendAiMessage('bot', '…');

    try {
        const res  = await fetch('api/ai.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ messages: aiHistory.slice(-5) }),
        });
        const data = await res.json();

        thinking.remove();

        const reply = data.reply || data.error || 'No response.';
        appendAiMessage('bot', reply);
        aiHistory.push({ role: 'model', parts: [{ text: reply }] });

    } catch (e) {
        thinking.textContent = 'Network error.';
    }
}

function appendAiMessage(role, text) {
    const msgs = document.getElementById('ai-messages');
    const div  = document.createElement('div');
    div.className = `ai-msg ${role}`;
    div.textContent = text;
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('ai-input');
    if (input) {
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') aiSend();
        });
    }
});