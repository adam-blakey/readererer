@props(['page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $page_subname }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <form class="space-y">
                            <div>
                                <div class="input-icon">
		                            <span class="input-icon-addon">
                                        <x-icon name="user" />
                                    </span>
                                    <input type="text" value="" class="form-control" placeholder="Username">
                                </div>
                            </div>
                            <div>
                                <div class="input-icon">
		                            <span class="input-icon-addon">
			                            <x-icon name="mail" />
                                    </span>
                                    <input type="email" value="" class="form-control" placeholder="Email address">
                                </div>
                            </div>
                            <div>
                                <div class="input-icon">
		                            <span class="input-icon-addon">
			                            <x-icon name="lock" />
                                    </span>
                                    <input type="password" value="" class="form-control" placeholder="Password">
                                </div>
                            </div>
                            <div>
                                <div class="input-icon">
		                            <span class="input-icon-addon">
			                            <x-icon name="lock" />
		                            </span>
                                    <input type="password" value="" class="form-control" placeholder="Confirm Password">
                                </div>
                            </div>
                            <div>
                                <div class="row">
                                    <div class="col-auto">
                                        <a href="#" class="btn btn-primary btn-3">
                                            Create Account
                                            <!-- TODO: use the 'right-arrow' icon with appropriate left padding -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon icon-end icon-2"><path d="M5 12l14 0"></path><path d="M13 18l6 -6"></path><path d="M13 6l6 6"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
