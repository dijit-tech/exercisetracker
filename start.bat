@echo off
echo ========================================
echo Starting Exercise Tracker (Local Dev)
echo ========================================
echo.
echo Building and starting Docker containers...
docker-compose up -d --build
echo.
echo Waiting for services to start...
timeout /t 10 /nobreak >nul
echo.
echo ========================================
echo   READY!
echo ========================================
echo.
echo   Application:  http://localhost:8000
echo   phpMyAdmin:   http://localhost:8080
echo   MySQL Port:   3307
echo.
echo   Test Login:
echo     Username: testuser
echo     Password: password123
echo.
echo   Admin Login:
echo     Username: admin
echo     Password: password123
echo.
echo Commands:
echo   View logs:    docker-compose logs -f web
echo   Stop:         docker-compose down
echo   Restart:      docker-compose restart
echo ========================================
