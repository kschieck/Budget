<html>
<head>
<style>
    input {
         margin-bottom: 5px;
    }

    #form-container {
        display: inline-block;
    }

    #submit-button {
        float: right;
    }
</style>
<meta name="viewport" content="width=device-width, initial-scale=1" />
</head>

<body>
    <form id="form-container" method="POST" action="/budget">
        <input id="username" type="text" placeholder="username"></input><br />
        <input id="password" type="password" placeholder="password"></input><br />
        <input id="submit-button" type="submit" onclick="submitAuth()"></input>
    </form>
</body>

</html>