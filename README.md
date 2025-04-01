<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Stock Price Tracker

Stock Price Tracker is backend system that integrates with the Alpha Vantage API, collects
and aggregates real time stock price data.

## Project setup

### Install Dependencies

To install all necessary dependencies, run the following command:

```bash
$ composer install
```

## Run Migrations and Seed Initial Data

To run migrations and seed initial data, execute:

```bash
$ php artisan migrate:fresh --seed 
```

### Real-Time Stock Price Setup

 follow these steps:

### 1. Set the Alpha Vantage API Key in `.env`

Before you can fetch real-time stock prices, you need to set your Alpha Vantage API key in the `.env` file. Open the `.env` file and add the following line, replacing `YOUR_API_KEY` with your actual API key:

```env
ALPHA_VANTAGE_API_KEY=YOUR_API_KEY
```

### 2. First terminal (Scheduler):

```bash
$ php artisan schedule:work
```

### 3. Second terminal (Queue Worker):

```bash
$ php artisan queue:work
```

The system enforces that each stock (stock_id) can only have one price record per exact timestamp. This prevents duplicate entries and ensures data accuracy.

## Postman collection

The Postman collection is located in the project directory as stock_price_tracker.postman_collection.json. It contains three endpoints:

1. Get Single Latest Price – Retrieve the latest price for a single stock.

2. Set or Get All Stock Latest Prices – Fetch or set the latest prices for all stocks.

3. Change Price Reporting – Report changes in stock prices.

### Why Two Distinct Endpoints?
While it’s technically possible to merge the functionality (1. and 2.) into one endpoint, I maintain two separate endpoints for semantic clarity. This makes it clear when you need data for a specific stock and allows for easier future expansions (e.g., adding stock-specific parameters). Additionally, this approach benefits caching, as stock-specific keys like stock:[ID]:latest will yield faster cache hits.






