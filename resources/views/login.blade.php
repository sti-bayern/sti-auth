<h1>STI-Auth</h1>
<form method="POST" action="{{config('sti-auth.route_login')}}">
    @csrf
    <input name="username" placeholder="username">
    <input name="password" type="password" placeholder="password">
    <button>Login</button>
</form>
