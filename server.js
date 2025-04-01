// Импортируем необходимые модули
var http = require('http');
var static = require('node-static');
var file = new static.Server('.', {
  cache: 0
});

// Функция обработки входящих запросов
function accept(req, res) {
  if (req.url == '/upload') {
    var length = 0;
    // Обрабатываем загрузку файла
    req.on('data', function(chunk) {
      length += chunk.length;
      if (length > 50 * 1024 * 1024) {
        res.statusCode = 413;
        res.end("File too big");
      }
    }).on('end', function() {
      res.end('ok'); 
    });
  } else {
    // Отдаем статические файлы
    file.serve(req, res);
  }
}

// Создаем и запускаем сервер
if (!module.parent) {
  http.createServer(accept).listen(8080);
} else {
  exports.accept = accept;
}