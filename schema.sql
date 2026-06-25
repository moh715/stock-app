-- Run this once to set up the database

CREATE DATABASE IF NOT EXISTS stockapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stockapp;

CREATE TABLE IF NOT EXISTS users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(64) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS stocks (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(128) NOT NULL,
    symbol VARCHAR(16) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS stock_cache (
    symbol     VARCHAR(16)  NOT NULL,
    type       ENUM('quote','time_series') NOT NULL,
    data       JSON         NOT NULL,
    fetched_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (symbol, type)
);
CREATE TABLE IF NOT EXISTS news_cache (
    cache_key  VARCHAR(64)  NOT NULL PRIMARY KEY,
    data       JSON         NOT NULL,
    fetched_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stock_user (
    stock_id INT NOT NULL,
    PRIMARY KEY (user_id, stock_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
);