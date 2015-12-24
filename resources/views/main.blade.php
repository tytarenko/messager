<!DOCTYPE html>
<html lang="en">
    <head>
        <title>API</title>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

        <style>
            h4 {
                margin-top: 25px;
            }
            .row {
                margin-bottom: 20px;
            }
            .row .row {
                margin-top: 10px;
                margin-bottom: 0;
            }
            [class*="col-"] {
                padding-top: 15px;
                padding-bottom: 15px;
                background-color: #eee;
                background-color: rgba(86,61,124,.15);
                border: 1px solid #ddd;
                border: 1px solid rgba(86,61,124,.2);
            }

            hr {
                margin-top: 40px;
                margin-bottom: 40px;
            }
        </style>
        <!-- Latest compiled and minified JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="container">
            <div class="page-header">
                <h1>Api</h1>
            </div>

            <h2>Пользователи</h2>
            <h4>Получение списка пользователей</h4>
            <p>Запрос GET на <b>/api/v1/users</b> возвращает список пользователей.</p>
            <p>По умолчанию, без параметров limit и(или) offset возвратит первых 10 пользователей. Для изменения количесва отдаваемых ползователей нужно передать следующий параметр limit и указать количесто. Максимально допустиое количество пользователей за один запрос не будет привышать 100 записей, если передано limit=500, то вернеться количество установленое по умолчанию, 10.</p>
            <p>Параметр offset позволяте организвать постраничную навигацию</p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users?limit=50&offset=100</p>
            </blockquote>
            <br>
            <p>Тип пользователя <b>all, online, offline</b>. По умолчанию, всегда <b>all</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users?type=all | online | offline</p>
            </blockquote>
            <br>
            <p>Можно запросить только нужные поля. Допустимые поля <b>id, username, email, status, created_at</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users?fields=id,username,status<status></status></p>
            </blockquote>
            <br>
            <p>Можно сортировать список пользователей по возрастанию(asc) или убыванию(desc). Допустимые поля для сортировки <b>id, username, email, status, created_at</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users?sort=id:asc,created_at:desc<status></status></p>
            </blockquote>
            <br>
            <br>
            <h4>Получения конкретного пользователя </h4>
            <p>Для получения конкретного пользователя нужно передать запрос GET на <b>/api/v1/users/{id}</b>, где <b>id</b> целое чило больше нуля, если пользователь не найден с таким id, сервер вернет сообщение об этом и статус 404</p>
            <br>
            <p>Можно запросить только нужные поля. Допустимые поля <b>id, username, email, status, created_at</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users/23?fields=id,username,status<status></status></p>
            </blockquote>
            <br>
            <br>
            <h4>Создание пользователя</h4>
            <p>Для того что бы создать пользователя, нужно передать запрос POST на <b>/api/v1/users</b> со следющими обязательными параметрами <b>username, email, password</b>. Сервер вернет все данные только что созданого пользователя. Длина username не может привышать 255 символов, а длина password должна быть больше 6 и не более 60</p>
            <br>
            <br>
            <h4>Обновление данных пользователя</h4>
            <p>Для того что бы польность обновить данные пользователя нужно передать запрос PUT на на <b>/api/v1/users/{id}</b>, где <b>id</b> целое чило больше нуля. Если пользователя с таким id не найдено он будет создан. Обязательные параметры <b>username, email, password, status</b></p>
            <p>Елси передать не все обязательные параметры сервер вернет сообщение что не все поля переданы и статус 400</p>
            <p>Для того что бы частично обновить данные пользователя(например статус) нужно передать запрос PATCH на на <b>/api/v1/users/{id}</b>, где <b>id</b> целое чило больше нуля. Если пользователя с таким id не найдено сервер вернет сообщение что он не найден и статус 404. Допустимые поля, но необязательные, <b>username, email, password, status</b></p>
            <br>
            <br>
            <h4>Удаление пользователя</h4>
            <p>Для того что бы удалить пользователя, нужно передать запрос DELETE на на <b>/api/v1/users/{id}</b>, где <b>id</b> целое чило больше нуля. Если удаляемый пользователь до этого не существовал, то сервер вернет сообщение что он не найден и статус 404. В случае успешного удаления сервер вернет статус 204</p>
            <br>
            <br>
            <hr>
            <br>
            <h2>Сообщения пользователей</h2>
            <h4>Получение списка сообщений конкретного пользователя</h4>
            <p>Запрос GET на <b>/api/v1/users/{user_id}/messages</b>  где <b>user_id</b> целое чило больше нуля, возвращает сообщений конкретного пользователя.</p>
            <p>По умолчанию, без параметров limit и(или) offset возвратит первых 50 сообщений. Для изменения количесва отдаваемых сообщений нужно передать следующий параметр limit и указать количесто. Максимально допустиое количество сообщений за один запрос не будет привышать 100 записей, если передано limit=500, то вернеться количество установленое по умолчанию, 10.</p>
            <p>Параметр offset позволяте организвать постраничную навигацию</p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users/{user_id}/messages?limit=50&offset=100</p>
            </blockquote>
            <br>
            <p>Тип сообщений <b>all, inbox, sent</b>. По умолчанию, всегда <b>all</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users/{user_id}/messages?type=all</p>
            </blockquote>
            <br>
            <p>Можно запросить только нужные поля. Допустимые поля <b>id, sender_id, receiver_id, subject, body, read, created_at</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users/{user_id}/messages?fields=id,username,status<status></status></p>
            </blockquote>
            <br>
            <p>Можно сортировать список сообщений по возрастанию(asc) или убыванию(desc). Допустимые поля для сортировки <b>id, sender_id, receiver_id, subject, body, read, created_at</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users/{user_id}/messages?sort=id:asc,created_at:desc<status></status></p>
            </blockquote>
            <br>
            <br>
            <h4>Получения конкретного сообщения конкретного пользователя </h4>
            <p>Для получения конкретного сообщения нужно передать запрос GET на <b>/api/v1/users/{user_id}/messages/{message_id}</b>, где <b>user_id, message_id</b> целое чило больше нуля, если сообщения не найдено с таким id, сервер вернет сообщение об этом и статус 404</p>
            <br>
            <p>Можно запросить только нужные поля. Допустимые поля b>id, sender_id, receiver_id, subject, body, read, created_at</b></p>
            <blockquote>
                <p><b>GET</b></p>
                <p>/api/v1/users/{user_id}/messages=id,receiver_id,read<status></status></p>
            </blockquote>
            <br>
            <br>
            <h4>Создание сообщения пользователя к пользователю</h4>
            <p>Для того что бы создать сообщение пользователю, нужно передать запрос POST на <b>/api/v1/users/{user_id}/messages/</b> со следющими обязательными параметрами <b>receiver_id, subject, body</b>. Сервер вернет все данные только что созданого сообщения. Длина subject не может привышать 255 символов</p>
            <br>
            <br>
            <h4>Обновление данных сообщения</h4>

            <p>Поскольку это упращенный месседжер, то обновить сообщение целиком не возможно. Только получатель сообщения может обновить поля read со значения false на trueДля того что бы обновить данные сообщения нужно передать запрос PUT или PATCH на на <b>/api/v1/users/{user_id}/messages/{message_id}</b>, где <b>user_id, message_id</b> целое чило больше нуля. Если пользователя или сообщение с такими id не найдено то сервер вернет сообщение что он не найден и статус 404</b></p>
            <br>
            <br>
            <h4>Удаление сообщения</h4>
            <p>Получатель или отправитель сообщения могут удалять свои входящие или исходящие сообщения.Для того что бы удалить сообщение, нужно передать запрос DELETE на <b>/api/v1/users/{user_id}/messages/{message_id}</b>, где <b>user_id, message_id</b> целое чило больше нуля. Если пользователь не найден или сообщение до этого не существовало, то сервер вернет сообщение и статус 404. В случае успешного удаления сервер вернет статус 204</p>

        </div>

    </body>
</html>