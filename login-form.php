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
    <div id="form-container">
        <input id="username" type="text" placeholder="username"></input><br />
        <input id="password" type="password" placeholder="password"></input><br />
        <button id="submit-button" onclick="submitAuth()">Submit</button>
    </div>
</body>

<script type="text/javascript">

function getData(url = "", auth) {
    // Default options are marked with *
    return fetch(url, {
        method: "GET", // *GET, POST, PUT, DELETE, etc.
        mode: "cors", // no-cors, *cors, same-origin
        credentials: "same-origin", // include, *same-origin, omit
        headers: {
            "Content-Type": "application/json",
            "Authorization": auth
        },
        redirect: "follow", // manual, *follow, error
        referrerPolicy: "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
    });
}

function submitAuth() {
    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;

    var authString = btoa(username + ":" + password);

    getData("./auth.php", "Basic " + authString).then(d => {
        window.location = "/budget";
    });

}

</script>

</html>