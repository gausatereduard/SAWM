<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title> Congratulations Page Design By WebJourney - Html Template </title>
    <style>
    .congratulation-wrapper {
        max-width: 550px;
        margin-inline: auto;
        -webkit-box-shadow: 0 0 20px #f3f3f3;
        box-shadow: 0 0 20px #f3f3f3;
        padding: 30px 20px;
        background-color: #fff;
        border-radius: 10px;
    }

    .congratulation-contents.center-text .congratulation-contents-icon {
        margin-inline: auto;
    }
    .congratulation-contents-icon {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
        height: 100px;
        width: 100px;
        background-color: #65c18c;
        color: #fff;
        font-size: 50px;
        border-radius: 50%;
        margin-bottom: 30px;
    }
    .congratulation-contents-title {
        font-size: 32px;
        line-height: 36px;
        margin: -6px 0 0;
    }
    .congratulation-contents-para {
        font-size: 16px;
        line-height: 24px;
        margin-top: 15px;
    }
    .btn-wrapper {
        display: block;
    }
    .cmn-btn.btn-bg-1 {
        background: #6176f6;
        color: #fff;
        border: 2px solid transparent;
        border-radius:3px;
        text-decoration: none;
        padding:5px;
    }
    </style>
</head>
<body>
    <!-- Congratulations area start -->
    <div class="congratulation-area text-center mt-5">
        <div class="container">
            <div class="congratulation-wrapper">
                <div class="congratulation-contents center-text">
                    <h4 class="congratulation-contents-title"> Public page! </h4>
                    <p class="congratulation-contents-para"> Everyone can see it. </p>
                    <div class="btn-wrapper">
                        <a class="cmn-btn btn-bg-1" href="guestbook.php">Open guestbook</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab 4 links -->
    <div class="congratulation-area text-center mt-5">
        <div class="container">
            <div class="congratulation-wrapper">
                <h4 class="congratulation-contents-title"> Lab 4 — Protecting Confidential Data </h4>
                <ul style="text-align: left; line-height: 1.9; margin-top: 15px;">
                    <li><a href="users.php">Users table / password storage</a></li>
                    <li><a href="hash-demo.php">MD5 / SHA-1 vs salted hashes</a></li>
                    <li><a href="migrate_passwords.php">Migrate plaintext passwords</a></li>
                    <li>
                        Secure:
                        <a href="secure/signup.php">signup</a> /
                        <a href="secure/login.php">login</a>
                    </li>
                    <li>
                        Vulnerable (demo only):
                        <a href="vulnerable/signup.php">signup</a> /
                        <a href="vulnerable/login.php">login</a>
                    </li>
                    <li><a href="guestbook.php">Guestbook (Lab 3)</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
