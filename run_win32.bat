if exist %start.php goto runbot
echo Error running mYsTeRy: File was not found
goto end

:runbot
php start.php

:end  
pause