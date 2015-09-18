# Symfony Security OAuth Redis

[![Build Status](https://travis-ci.org/cultuurnet/symfony-security-oauth-redis.svg?branch=master)](https://travis-ci.org/cultuurnet/symfony-security-oauth-redis) [![Coverage Status](https://coveralls.io/repos/cultuurnet/symfony-security-oauth-redis/badge.svg?branch=master&service=github)](https://coveralls.io/github/cultuurnet/symfony-security-oauth-redis?branch=master)

## Intro

This library provides a NonceProvider implementation that will register nonce and timestamp in redis.
It also adds TokenProviderCache that adds caching of tokens in redis. (using the decorator pattern)

## Setup

This library can be used in composer.json and be set up in combination with cultuurnet/symfony-security-oauth
and cultuurnet/symfony-security-oauth-uitid.
For developer purposed you can clone it and run `composer install`.
