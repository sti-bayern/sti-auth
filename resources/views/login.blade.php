<h1>STI-Auth</h1>
<form method="POST" action="{{config('sti-auth.route_login')}}">
    @csrf
    <input name="email" placeholder="email">
    <input name="password" type="password" placeholder="password">
    <button>Login</button>
</form>
