<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client jQuery</title>
    <link rel="stylesheet" href="/assets/css/pico.min.css">
    <script src="/assets/js/jquery-3.7.1.min.js"></script>
</head>
<body>
<main class="container">
    <h1>Client jQuery</h1>

    <form id="login-form">
        <label for="login-email">Email</label>
        <input type="email" id="login-email" name="email" required>

        <label for="login-password">Mot de passe</label>
        <input type="password" id="login-password" name="password" required>

        <button type="submit">Se connecter</button>
    </form>
    <form id="logout-form">
        <button type="submit">Se déconnecter</button>
    </form>

    <form id="update-form">
        <h2>Modifier mon compte</h2>

        <label for="update-pseudo">Pseudo</label>
        <input type="text" id="update-pseudo" name="pseudo" maxlength="50">

        <label for="update-gender">Genre</label>
        <select id="update-gender" name="gender">
            <option value="">Ne pas changer</option>
            <option value="m">Homme</option>
            <option value="f">Femme</option>
            <option value="o">Autre</option>
        </select>

        <label for="update-avatar">Avatar</label>
        <input type="text" id="update-avatar" name="avatar" maxlength="250">

        <button type="submit">Enregistrer</button>
    </form>

    <form id="delete-form">
        <button type="submit">Supprimer mon compte</button>
    </form>

    <form id="register-form">
        <h2>Créer un compte</h2>

        <label for="register-first-name">Prénom</label>
        <input type="text" id="register-first-name" name="first_name" maxlength="50" required>

        <label for="register-last-name">Nom</label>
        <input type="text" id="register-last-name" name="last_name" maxlength="50" required>

        <label for="register-pseudo">Pseudo</label>
        <input type="text" id="register-pseudo" name="pseudo" maxlength="50" required>

        <label for="register-birth-date">Date de naissance</label>
        <input type="date" id="register-birth-date" name="birth_date" required>

        <label for="register-gender">Genre</label>
        <select id="register-gender" name="gender" required>
            <option value="m">Homme</option>
            <option value="f">Femme</option>
            <option value="o">Autre</option>
        </select>

        <label for="register-avatar">Avatar (facultatif)</label>
        <input type="text" id="register-avatar" name="avatar" maxlength="250">

        <label for="register-email">Email</label>
        <input type="email" id="register-email" name="email" maxlength="250" required>

        <label for="register-password">Mot de passe</label>
        <input type="password" id="register-password" name="password" required>

        <button type="submit">Créer le compte</button>
    </form>

    <p id="auth-message" role="alert"></p>
</main>

<script>
    function authenticate(email, password) {
        $.ajax({
            url: 'http://localhost:8000/api/login',
            method: 'POST',
            data: {email: email, password: password},
            success: function (response) {
                const accessToken = response.data.accessToken;
                localStorage.setItem('accessToken', accessToken);
                $('#auth-message').text('Connexion réussie.');
                $('#login-form')[0].reset();
                updateAuthView();
            },
            error: function (xhr) {
                if (xhr.status === 401) {
                    $('#auth-message').text('Email ou mot de passe incorrect.');
                } else {
                    $('#auth-message').text('Une erreur est survenue, veuillez réessayer.');
                }
            }
        });
    }

    function requestWithRefresh(options) {
        const withToken = function (extra) {
            return $.extend({}, options, extra, {
                headers: {'Authorization': 'Bearer ' + localStorage.getItem('accessToken')}
            });
        };

        $.ajax(withToken({
            error: function (xhr) {
                if (xhr.status !== 401) {
                    options.error(xhr);
                    return;
                }
                reauthenticate(function () {
                    $.ajax(withToken({}));
                });
            }
        }));
    }

    function expireSession() {
        localStorage.removeItem('accessToken');
        $('#auth-message').text('Votre session a expiré, veuillez vous reconnecter.');
        updateAuthView();
    }

    function deauthenticate() {
        requestWithRefresh({
            url: 'http://localhost:8000/api/logout',
            method: 'POST',
            success: function () {
                localStorage.removeItem('accessToken');
                $('#auth-message').text('Déconnexion réussie.');
                updateAuthView();
            },
            error: function (xhr) {
                if (xhr.status === 401) {
                    expireSession();
                } else {
                    $('#auth-message').text('La déconnexion a échoué, veuillez réessayer.');
                }
            }
        });
    }

    function reauthenticate(callback) {
        $.ajax({
            url: 'http://localhost:8000/api/token/refresh',
            method: 'POST',
            success: function (response) {
                const accessToken = response.data.accessToken;
                localStorage.setItem('accessToken', accessToken);
                if (callback) callback(response.data.accessToken);
            },
            error: function () {
                expireSession();
            }
        });
    }

    function endpointCreate(data) {
        $.ajax({
            url: 'http://localhost:8000/api/user',
            method: 'POST',
            data: data,
            success: function () {
                $('#auth-message').text('Compte créé, vous pouvez vous connecter.');
                $('#register-form')[0].reset();
            },
            error: function (xhr) {
                if (xhr.status === 409) {
                    $('#auth-message').text('Ce pseudo ou cet email est déjà utilisé.');
                } else {
                    $('#auth-message').text('La création du compte a échoué, veuillez réessayer.');
                }
            }
        });
    }

    function endpointUpdate(id, data) {
        requestWithRefresh({
            url: 'http://localhost:8000/api/user' + '/' + id,
            method: 'PATCH',
            data: JSON.stringify(data),
            success: function () {
                $('#auth-message').text('Compte modifié.');
                $('#update-form')[0].reset();
            },
            error: function (xhr) {
                if (xhr.status === 401) {
                    expireSession();
                } else if (xhr.status === 403) {
                    $('#auth-message').text('Vous ne pouvez modifier que votre propre compte.');
                } else if (xhr.status === 409) {
                    $('#auth-message').text('Ce pseudo est déjà utilisé.');
                } else {
                    $('#auth-message').text('La modification a échoué, veuillez réessayer.');
                }
            }
        });
    }

    function endpointDelete(id) {
        requestWithRefresh({
            url: 'http://localhost:8000/api/user' + '/' + id,
            method: 'DELETE',
            success: function () {
                localStorage.removeItem('accessToken');
                $('#auth-message').text('Compte supprimé.');
                updateAuthView();
            },
            error: function (xhr) {
                if (xhr.status === 401) {
                    expireSession();
                } else if (xhr.status === 403) {
                    $('#auth-message').text('Vous ne pouvez supprimer que votre propre compte.');
                } else {
                    $('#auth-message').text('La suppression a échoué, veuillez réessayer.');
                }
            }
        });
    }

    $('#login-form').on('submit', function (event) {
        event.preventDefault();

        const email = $('#login-email').val();
        const password = $('#login-password').val();

        authenticate(email, password);
    });

    $('#logout-form').on('submit', function (event) {
        event.preventDefault();

        deauthenticate();
    });

    $('#register-form').on('submit', function (event) {
        event.preventDefault();

        endpointCreate($(this).serialize());
    });

    $('#update-form').on('submit', function (event) {
        event.preventDefault();

        const data = {
            pseudo: $('#update-pseudo').val(),
            gender: $('#update-gender').val(),
            avatar: $('#update-avatar').val()
        };

        if (!data.pseudo && !data.gender && !data.avatar) {
            $('#auth-message').text('Remplissez au moins un champ.');
            return;
        }

        endpointUpdate(getCurrentUserId(), data);
    });

    $('#delete-form').on('submit', function (event) {
        event.preventDefault();

        if (confirm('Supprimer définitivement votre compte ?')) {
            endpointDelete(getCurrentUserId());
        }
    });

    function getTokenPayload() {
        try {
            const payload = localStorage.getItem('accessToken').split('.')[1];
            return JSON.parse(atob(payload.replace(/-/g, '+').replace(/_/g, '/')));
        } catch (e) {
            return null;
        }
    }

    function getCurrentUserId() {
        return getTokenPayload().user_id;
    }

    function updateAuthView() {
        const isAuthenticated = localStorage.getItem('accessToken') !== null;

        $('#login-form').toggle(!isAuthenticated);
        $('#logout-form').toggle(isAuthenticated);
        $('#update-form').toggle(isAuthenticated);
        $('#delete-form').toggle(isAuthenticated);
        $('#register-form').toggle(!isAuthenticated);
    }

    updateAuthView();

    if (localStorage.getItem('accessToken') !== null) {
        const storedPayload = getTokenPayload();
        if (!storedPayload || storedPayload.exp < Date.now() / 1000) {
            reauthenticate();
        }
    }
</script>
</body>
</html>
