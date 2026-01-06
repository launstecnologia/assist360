<?php

namespace App\Models;

use App\Core\Database;

class WhatsappTemplate extends Model
{
	protected string $table = 'whatsapp_templates';
	protected array $fillable = ['nome', 'tipo', 'corpo', 'variaveis', 'ativo', 'padrao', 'created_at', 'updated_at'];

	public static function ensureTable(): void
	{
		$sql = "CREATE TABLE IF NOT EXISTS whatsapp_templates (
			id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			nome VARCHAR(150) NOT NULL,
			tipo VARCHAR(60) NOT NULL,
			corpo TEXT NOT NULL,
			variaveis JSON NULL,
			ativo TINYINT(1) NOT NULL DEFAULT 1,
			padrao TINYINT(1) NOT NULL DEFAULT 0,
			created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		Database::query($sql);
	}
}


