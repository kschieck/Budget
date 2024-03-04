<?php
$authed = isset($_SESSION["budget_auth"]);
?>

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
    <form id="form-container" method="post" action="/budget/">
        <fieldset id="login_fieldset">
            <input id="username" type="text" name="username" placeholder="username"></input><br />
            <input id="password" type="password" name="password" placeholder="password"></input><br />
            <input id="submit-button" type="submit"></input>
        </fieldset>
    </form>
<script>

function postData(url = "", data = {}) {
    // Default options are marked with *
    return fetch(url, {
        method: "POST", // *GET, POST, PUT, DELETE, etc.
        mode: "cors", // no-cors, *cors, same-origin
        cache: "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
        credentials: "same-origin", // include, *same-origin, omit
        headers: {
            "Content-Type": "application/json",
        },
        redirect: "follow", // manual, *follow, error
        referrerPolicy: "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: JSON.stringify(data), // body data type must match "Content-Type" header
    });
}

function tryLogin() {
    var shouldAuth = <?=!$authed? 1 : 0?>;

    // Use token from local storage to automatically authenticate session
    const token = localStorage.getItem("rememberme");
    if (token && shouldAuth) {
        var fieldSet = document.getElementById("login_fieldset");
        fieldSet.disabled = true;
        postData("/budget/auth.php", {rememberme: token}).then((data) => {
            if (data.status == 200) {
                window.location = "/budget";
            } else {
                fieldSet.disabled = false;
                localStorage.removeItem("rememberme");
            }
        }).catch(e => {
            fieldSet.disabled = false;
        })
    }
}

tryLogin();

</script>
</body>

</html>