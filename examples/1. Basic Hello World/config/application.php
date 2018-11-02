<?php

return [
  "debug" => (getenv("SYPLEX_ENV") === "dev"),
  "rootips" => [
    "127.0.0.1"
  ],
  "cache" => [
    "expire" => 2592000,
    "driver" => "sqlite",
    "enabled" => true,
    "debug" => false
  ],
  "encryption" => [
    "key" => "--__PUT_YOUR_KEY_HERE__--",
    "cipher" => "aes-256-cbc"
  ]
];