import { Routes } from '@angular/router';
import { LoginComponent } from './components/login/login.component';
import { HomeComponent } from './components/home/home.component';
import { UserComponent } from './components/user/user.component';
import { ErrorComponent } from './components/error/error.component';
import { LodgingComponent } from './components/lodging/lodging.component';
import { ConfigurationComponent } from './components/configuration/configuration.component';
import { RegisterComponent } from './components/register/register.component';
import { AdministratorGuard } from './services/administrator.guard';
import { AlreadyLoggedInGuard } from './services/already_logged_in.guard';
import { LogInGuard } from './services/login.guard';
import { LodgingInfoComponent } from './components/lodging-info/lodging-info.component';

export const routes: Routes = [
    {path: 'lodging', component: LodgingComponent, canActivate: [LogInGuard]},
    {path: 'lodging/create', component: LodgingInfoComponent, canActivate: [LogInGuard]},
    {path: 'lodging/:id', component: LodgingInfoComponent, canActivate: [LogInGuard]},
    {path: 'login', component: LoginComponent, canActivate: [AlreadyLoggedInGuard]},
    {path: 'register', component: RegisterComponent, canActivate: [AlreadyLoggedInGuard]},
    {path: 'home', component: HomeComponent},
    {path: 'user', component: UserComponent, canActivate: [LogInGuard, AdministratorGuard]},
    {path: 'configuration', component:ConfigurationComponent},
    { path: '', redirectTo: '/login', pathMatch: 'full' },
    {path: '**', component: ErrorComponent}
];
