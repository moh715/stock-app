# StockView

A stock market viewing web app where you can track stocks, read market news, and get AI-powered insights.

---

## Features

- Browse trending stocks with live price charts
- Search any stock, ETF, or cryptocurrency
- Pin stocks to your personal watchlist
- View detailed stock info and latest news for each stock
- Read general market and business headlines
- Ask an AI assistant about any stock or market topic
- Dark mode via system preference

---

## Requirements

- XAMPP (Apache + PHP 8+ + MySQL)
- API keys for: [Twelve Data](https://twelvedata.com), [NewsAPI](https://newsapi.org), [Google Gemini](https://aistudio.google.com)

---

## Setup

1. Run `schema.sql` then `seed.sql` in phpMyAdmin
2. Fill in your keys in `.env`


---

## Notes

- NewsAPI only works from `localhost` on the free plan
- S&P 500 / Nasdaq indices require a paid Twelve Data plan — use `SPY` and `QQQ` instead
- Crypto symbols use the `BASE/QUOTE` format (e.g. `BTC/USD`)
