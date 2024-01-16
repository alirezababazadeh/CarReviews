docker-compose exec worker php bin/console doctrine:migrations:generate
docker-compose exec worker php bin/console doctrine:migrations:migrate
