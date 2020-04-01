<!DOCTYPE html>
<html>
<head>
  <title>404 Page Not Found</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.css">
  
  <style type="text/css">
    body {
      text-align: center;
      background-color: #efefef;
    }

    .block {
      display: inline-block;
      box-shadow: 1px 1px 1px #ccc;
      font-family: Helvetica, Sans-serif;
      background-color: white;
      margin-top: 30px;
      border-radius: 5px;
      width: 600px;
      text-align: left;
      padding: 30px;
      color: #888;
      box-sizing: border-box;
    }

    p {
      line-height: 24px;
    }

    h2 {
      color: #555;
      border-bottom: 1px solid #efefef;
      padding-bottom: 10px;
    }
    .params {
      border: 1px solid #ccc;
      padding: 15px;
      background-color: #efefef;

    }
  </style>
</head>
<body>

  <div class="block">
    <h2>404 Page Not Found.</h2> 
    <p>
      The URL 
      <strong>
        <?= "{$_GET['controller']}/{$_GET['action']}" ?>
      </strong>
      was not found.
    </p>
    <? if (ENV !== 'production'): ?>
      <h3>Parameters</h3>
      <div class="params">
        <?= trace(params()); ?>
      </div>
    <? endif ?>
  </div>

</body>
</html>
