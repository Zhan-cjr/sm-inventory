.PHONY: up down restart logs ps migrate seed tinker bash-backend bash-ai

up:
	docker compose up -d --build

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

ps:
	docker compose ps

migrate:
	docker compose exec backend php artisan migrate

seed:
	docker compose exec backend php artisan db:seed

tinker:
	docker compose exec backend php artisan tinker

bash-backend:
	docker compose exec backend bash

bash-ai:
	docker compose exec ai-service bash
