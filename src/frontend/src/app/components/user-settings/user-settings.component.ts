import { Component, OnInit } from '@angular/core';
import { UserService } from '../../services/user.service';
import { ActivatedRoute } from '@angular/router';
import { User } from '../../models/user';
import { firstValueFrom, merge } from 'rxjs';
import { FormsModule, ReactiveFormsModule, FormControl, Validators, NgForm, NgModel, FormGroup } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { MatButton } from '@angular/material/button';
import { NotificationService } from '../../services/notification.service';
import { AppResponse } from '../../models/app_response';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-user-settings',
  standalone: true,
  imports: [FormsModule, MatFormFieldModule, MatInputModule, ReactiveFormsModule, FormsModule, MatButton],
  templateUrl: './user-settings.component.html',
  styleUrl: './user-settings.component.css'
})
export class UserSettingsComponent implements OnInit {
  user!:User;
  errorMessage = '';
  userModifyFormGroup:FormGroup = this.buildFormGroup();
  userImageFile!: File | null;
  userImageData: any;
  sentImage = false;

  public constructor(
    private _userService:UserService,
    private _route:ActivatedRoute,
    private _notificationService: NotificationService,
  ){ }

  async ngOnInit() {
    let userName = this._route.snapshot.paramMap.get('name');
    if (userName !== null) {
      this.user = await firstValueFrom(this._userService.getUser(userName));
    }
    this.userModifyFormGroup.get<string>("first_name")!.setValue(this.user.first_name);
    this.userModifyFormGroup.get<string>("last_name")!.setValue(this.user.last_name);
    this.userModifyFormGroup.get<string>("email_address")!.setValue(this.user.email_address);
    this.userModifyFormGroup.get<string>("phone_number")!.setValue(this.user.phone_number);
  }

  public onSubmitUserSettings(){
    if(this.sentImage===false){
      return;
    }

    let userName = this._route.snapshot.paramMap.get('name');
    let first_name = this.userModifyFormGroup.get<string>("first_name")!;
    let last_name = this.userModifyFormGroup.get<string>("last_name")!;
    let email_user = this.userModifyFormGroup.get<string>("email_address")!;
    let phone_number = this.userModifyFormGroup.get<string>("phone_number")!;

    if(this.userModifyFormGroup.invalid){
      if(first_name.hasError("required")) {
        this._notificationService.show("El nombre debe tener un valor");
      }
      if(last_name.hasError("required")) {
        this._notificationService.show("El apellido debe tener un valor");
      }
      if(phone_number.hasError("required")) {
        this._notificationService.show("El numero de telefono debe tener un valor");
      }

      return;
    }

    let data = [];
    if(this.user.first_name===first_name.value){
      data[0] = '';
    }else{
      data[0] = first_name.value;
    }
    if(this.user.last_name===last_name.value){
      data[1] = '';
    }else{
      data[1] = last_name.value;
    }
    if(this.user.email_address===email_user.value){
      data[2] = '';
    }else{
      data[2] = email_user.value;
    }
    if(this.user.phone_number===phone_number.value){
      data[3] = 0;
    }else{
      data[3] = phone_number.value;
    }
    console.log(data);
    this._userService.updateUser(data,userName!).subscribe(
      response => {
        if(AppResponse.success(response)){
          Swal.fire({
            icon: "success",
            title: "Se modifico al usuario con exito"
          });
        }
        else {
          Swal.fire({
            icon: "error",
            title: "Ha ocurrido un error"
          });
        }
      }
    )
  }

  private buildFormGroup() {
    return new FormGroup({
      first_name: new FormControl(this.user?.first_name, { nonNullable: true, validators: Validators.required }),
      last_name: new FormControl(this.user?.last_name, { nonNullable: true, validators: Validators.required }),
      email_address: new FormControl(this.user?.email_address, { nonNullable: true, validators: Validators.required}),
      phone_number: new FormControl(this.user?.phone_number, { nonNullable: true, validators: Validators.required }),
    });
  }

  onUserImageChanged(event: any) {
    this.sentImage = true;
    this.userImageFile = event.target.files[0];

    let reader = new FileReader();
    reader.onload = e => this.userImageData = reader.result;

    reader.readAsDataURL(this.userImageFile!);

    this.submitUserImage();
  }

  
  undoImageChange() {
    this.userImageFile = null;
    this.userImageData = null;
  }

  submitUserImage(){
    if(this.user != null && this.userImageFile != null) {
      this._userService.saveUserImage(this.user.name, this.userImageFile).subscribe(
        response => {
          if(AppResponse.success(response)) {
            this.undoImageChange();
            this.user!.image = response.data;
            Swal.fire({
              icon:'success',
              title:'Se cambio la imagen correctamente'
            })
          }else{
            Swal.fire({
              icon: "error",
              title: "Ha ocurrido un error"
            });
          }
        }
      )
    }
    this.sentImage=true;
  }
}
