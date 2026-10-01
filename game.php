<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Космический Шутер — Динамическая сложность</title>
    <style>
        body {
            background: #05050a;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            margin: 0;
            padding: 20px;
            overflow: hidden;
        }
        h1 {
            margin: 5px 0;
            font-size: 24px;
            letter-spacing: 2px;
            color: #00ffff;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
        }
        canvas {
            background: #000;
            border: 2px solid #00ffff55;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.2);
            display: block;
            margin: 10px auto;
            border-radius: 8px;
        }
        .info {
            color: #888;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <h1>КОСМИЧЕСКИЙ СТРАЖ</h1>
    <canvas id="gameCanvas" width="800" height="600"></canvas>
    <div class="info">
        Управление: <b>Стрелки влево/вправо</b> — движение, <b>Пробел</b> — огонь!
    </div>

    <script>
        const canvas = document.getElementById("gameCanvas");
        const ctx = canvas.getContext("2d");

        let score = 0;
        let gameOver = false;

        // Корабль игрока
        let player = {
            x: canvas.width / 2 - 20,
            y: canvas.height - 70,
            width: 40,
            height: 40,
            speed: 7
        };

        let bullets = [];       // Пули игрока
        let enemyBullets = [];  // Пули врагов
        let enemies = [];       // Астероиды и вражеские корабли
        let particles = [];     // Частицы взрывов
        let stars = [];         // Звездное небо
        let keys = {};

        // Создаем звездное небо
        for (let i = 0; i < 100; i++) {
            stars.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                size: Math.random() * 2,
                speed: Math.random() * 1.5 + 0.5
            });
        }

        // Управление клавиатурой
        window.addEventListener("keydown", (e) => {
            keys[e.code] = true;
            if (e.code === "Space" && !gameOver) {
                // Двойной выстрел из пушек игрока
                bullets.push({
                    x: player.x + 5,
                    y: player.y,
                    width: 4,
                    height: 14,
                    speed: 10
                });
                bullets.push({
                    x: player.x + player.width - 9,
                    y: player.y,
                    width: 4,
                    height: 14,
                    speed: 10
                });
            }
        });

        window.addEventListener("keyup", (e) => {
            keys[e.code] = false;
        });

        // Эффект взрыва
        function createExplosion(x, y, color) {
            for (let i = 0; i < 15; i++) {
                particles.push({
                    x: x,
                    y: y,
                    dx: (Math.random() - 0.5) * 6,
                    dy: (Math.random() - 0.5) * 6,
                    size: Math.random() * 3 + 1,
                    life: 30,
                    color: color
                });
            }
        }

        // Спавн врагов (частота и скорость зависят от счета)
        function spawnEnemy() {
            // Чем больше очков, тем выше шанс спавна (максимум до определенного предела)
            let spawnChance = 0.02 + Math.min(score * 0.0003, 0.035);

            if (Math.random() < spawnChance) {
                let isAsteroid = Math.random() > 0.4;
                let size = isAsteroid ? Math.random() * 20 + 30 : 40;
                
                // Бонус к скорости от счета
                let speedMultiplier = 1 + (score * 0.005);

                enemies.push({
                    x: Math.random() * (canvas.width - size),
                    y: -size,
                    width: size,
                    height: size,
                    speed: (Math.random() * 2 + 1.5) * speedMultiplier,
                    type: isAsteroid ? 'asteroid' : 'ship',
                    angle: Math.random() * Math.PI,
                    shootTimer: 0 // Таймер для стрельбы врагов
                });
            }
        }

        // Обновление логики игры
        function update() {
            if (gameOver) return;

            // Динамическая скорость звезд (тоже ускоряется со счетом)
            let starSpeedBonus = 1 + (score * 0.002);
            stars.forEach(star => {
                star.y += star.speed * starSpeedBonus;
                if (star.y > canvas.height) star.y = 0;
            });

            // Движение игрока
            if (keys["ArrowLeft"] && player.x > 0) player.x -= player.speed;
            if (keys["ArrowRight"] && player.x < canvas.width - player.width) player.x += player.speed;

            // Частицы взрывов
            particles.forEach((p, index) => {
                p.x += p.dx;
                p.y += p.dy;
                p.life--;
                if (p.life <= 0) particles.splice(index, 1);
            });

            // Движение пуль игрока
            bullets.forEach((bullet, index) => {
                bullet.y -= bullet.speed;
                if (bullet.y < 0) bullets.splice(index, 1);
            });

            // Движение пуль врагов
            enemyBullets.forEach((bullet, index) => {
                bullet.y += bullet.speed;
                // Проверка попадания пули врага по игроку
                if (
                    bullet.x < player.x + player.width &&
                    bullet.x + bullet.width > player.x &&
                    bullet.y < player.y + player.height &&
                    bullet.y + bullet.height > player.y
                ) {
                    gameOver = true;
                    createExplosion(player.x + player.width/2, player.y + player.height/2, "#00ffcc");
                }
                if (bullet.y > canvas.height) enemyBullets.splice(index, 1);
            });

            // Спавн и логика врагов
            spawnEnemy();
            enemies.forEach((enemy, eIndex) => {
                enemy.y += enemy.speed;
                if (enemy.type === 'asteroid') enemy.angle += 0.02;

                // Если это вражеский корабль — он стреляет
                if (enemy.type === 'ship') {
                    enemy.shootTimer++;
                    // Стреляет примерно раз в 1-1.5 секунды (частота растет со счетом)
                    let shootInterval = Math.max(40, 90 - Math.floor(score * 0.2));
                    if (enemy.shootTimer >= shootInterval) {
                        enemy.shootTimer = 0;
                        enemyBullets.push({
                            x: enemy.x + enemy.width / 2 - 2,
                            y: enemy.y + enemy.height,
                            width: 4,
                            height: 12,
                            speed: 5 + (score * 0.003)
                        });
                    }
                }

                // Столкновение врага с игроком
                if (
                    player.x < enemy.x + enemy.width &&
                    player.x + player.width > enemy.x &&
                    player.y < enemy.y + enemy.height &&
                    player.y + player.height > enemy.y
                ) {
                    gameOver = true;
                    createExplosion(player.x + player.width/2, player.y + player.height/2, "#00ffcc");
                }

                // Пропуск врага мимо экрана
                if (enemy.y > canvas.height) {
                    enemies.splice(eIndex, 1);
                }

                // Попадание пули игрока во врага
                bullets.forEach((bullet, bIndex) => {
                    if (
                        bullet.x < enemy.x + enemy.width &&
                        bullet.x + bullet.width > enemy.x &&
                        bullet.y < enemy.y + enemy.height &&
                        bullet.y + bullet.height > enemy.y
                    ) {
                        createExplosion(enemy.x + enemy.width/2, enemy.y + enemy.height/2, enemy.type === 'asteroid' ? "#888" : "#ff5500");
                        enemies.splice(eIndex, 1);
                        bullets.splice(bIndex, 1);
                        score += enemy.type === 'asteroid' ? 10 : 25;
                    }
                });
            });
        }

        // Отрисовка графики
        function draw() {
            ctx.fillStyle = "#020205";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Звезды
            ctx.fillStyle = "#ffffff";
            stars.forEach(star => {
                ctx.globalAlpha = Math.random() * 0.5 + 0.5;
                ctx.fillRect(star.x, star.y, star.size, star.size);
            });
            ctx.globalAlpha = 1.0;

            // Частицы взрывов
            particles.forEach(p => {
                ctx.fillStyle = p.color;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();
            });

            // Корабль игрока
            ctx.shadowBlur = 15;
            ctx.shadowColor = "#00ffff";
            ctx.fillStyle = "#00ffff";
            ctx.beginPath();
            ctx.moveTo(player.x + player.width / 2, player.y);
            ctx.lineTo(player.x + player.width, player.y + player.height);
            ctx.lineTo(player.x + player.width / 2, player.y + player.height - 10);
            ctx.lineTo(player.x, player.y + player.height);
            ctx.closePath();
            ctx.fill();

            // Огонь двигателя
            ctx.fillStyle = "#ff5500";
            ctx.beginPath();
            ctx.moveTo(player.x + 12, player.y + player.height - 5);
            ctx.lineTo(player.x + player.width / 2, player.y + player.height + 8 + Math.random() * 6);
            ctx.lineTo(player.x + player.width - 12, player.y + player.height - 5);
            ctx.fill();
            ctx.shadowBlur = 0;

            // Пули игрока
            ctx.shadowBlur = 10;
            ctx.shadowColor = "#ff0055";
            ctx.fillStyle = "#ff3366";
            bullets.forEach(bullet => {
                ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
            });

            // Пули врагов (желто-оранжевые лазеры)
            ctx.shadowColor = "#ffaa00";
            ctx.fillStyle = "#ffcc00";
            enemyBullets.forEach(bullet => {
                ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
            });
            ctx.shadowBlur = 0;

            // Враги и астероиды
            enemies.forEach(enemy => {
                if (enemy.type === 'asteroid') {
                    ctx.save();
                    ctx.translate(enemy.x + enemy.width / 2, enemy.y + enemy.height / 2);
                    ctx.rotate(enemy.angle);
                    ctx.fillStyle = "#777788";
                    ctx.strokeStyle = "#555566";
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    let r = enemy.width / 2;
                    for (let i = 0; i < 6; i++) {
                        let angle = (i * Math.PI) / 3;
                        let xCoord = Math.cos(angle) * (r * (0.8 + (i % 2) * 0.3));
                        let yCoord = Math.sin(angle) * (r * (0.8 + (i % 2) * 0.3));
                        if (i === 0) ctx.moveTo(xCoord, yCoord);
                        else ctx.lineTo(xCoord, yCoord);
                    }
                    ctx.closePath();
                    ctx.fill();
                    ctx.stroke();
                    ctx.restore();
                } else {
                    // Вражеский корабль
                    ctx.fillStyle = "#ff6600";
                    ctx.beginPath();
                    ctx.moveTo(enemy.x + enemy.width / 2, enemy.y + enemy.height);
                    ctx.lineTo(enemy.x + enemy.width, enemy.y);
                    ctx.lineTo(enemy.x + enemy.width / 2, enemy.y + 10);
                    ctx.lineTo(enemy.x, enemy.y);
                    ctx.closePath();
                    ctx.fill();
                }
            });

            // Счет
            ctx.fillStyle = "#ffffff";
            ctx.font = "bold 20px 'Segoe UI', Arial";
            ctx.fillText("СЧЕТ: " + score, 25, 35);

            // Экран конца игры
            if (gameOver) {
                ctx.fillStyle = "rgba(5, 5, 10, 0.85)";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.fillStyle = "#ff3355";
                ctx.shadowBlur = 20;
                ctx.shadowColor = "#ff3355";
                ctx.font = "bold 42px 'Segoe UI', Arial";
                ctx.textAlign = "center";
                ctx.fillText("ИГРА ОКОНЧЕНА", canvas.width / 2, canvas.height / 2 - 25);
                ctx.shadowBlur = 0;

                ctx.fillStyle = "#ffffff";
                ctx.font = "20px 'Segoe UI', Arial";
                ctx.fillText("Ваш результат: " + score, canvas.width / 2, canvas.height / 2 + 15);
                
                ctx.fillStyle = "#00ffff";
                ctx.font = "16px 'Segoe UI', Arial";
                ctx.fillText("Нажмите F5 для перезапуска", canvas.width / 2, canvas.height / 2 + 60);
                ctx.textAlign = "left";
            }
        }

        // Игровой цикл
        function loop() {
            update();
            draw();
            requestAnimationFrame(loop);
        }

        loop();
    </script>
</body>
</html>