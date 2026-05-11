// `ng build --prod` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.

export const environment = {
  production: false,
  apiEndpoint: 'https://localhost:8000',
  csharpApiEndpoint: 'https://localhost:7059',
  vapidPublicKey: 'BHHbTzFgxZYqc9M63mm7SHNVpr26pg5QOa8q3DwKdJhsIwoaDalsk_AUYHrNs6JHYa8SyRwtHyHDapDlZ6QB_EQ',
  patreon: {
    clientId: 'SE5DppBaGLasCs4Hdq1kjhtHYVUpchvf7qU9oejSenPYoKuvRxOzeyYtz21zKZsL',
    redirectUri: 'https://api.poppyseedpets.com/patreon/connectAccount',
  }
};

/*
 * For easier debugging in development mode, you can import the following file
 * to ignore zone related error stack frames such as `zone.run`, `zoneDelegate.invokeTask`.
 *
 * This import should be commented out in production mode because it will have a negative impact
 * on performance if an error is thrown.
 */
// import 'zone.js/plugins/zone-error';  // Included with Angular CLI.
