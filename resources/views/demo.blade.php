<h1>LaraAuth Demo</h1>
<form method="POST" action="/api/login">
    @csrf
    <input name="email" placeholder="email">
    <input name="password" type="password" placeholder="password">
    <button>Login</button>
</form>
