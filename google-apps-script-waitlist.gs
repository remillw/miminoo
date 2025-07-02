/**
 * Script Google Apps Script pour collecter les emails de la waitlist
 * 
 * INSTRUCTIONS POUR LA CONFIGURATION :
 * 
 * 1. Créez un nouveau Google Sheet avec ces colonnes en ligne 1 :
 *    A1: Email | B1: Role | C1: Date | D1: Source
 * 
 * 2. Copiez l'ID de votre Google Sheet depuis l'URL
 *    https://docs.google.com/spreadsheets/d/VOTRE_SHEET_ID/edit
 * 
 * 3. Remplacez VOTRE_SHEET_ID ci-dessous par votre vrai ID
 * 
 * 4. Déployez ce script en tant que Web App :
 *    - Allez dans "Déployer" > "Nouvelle déploiement"
 *    - Type : Application web
 *    - Exécuter en tant que : Moi
 *    - Accès autorisé à : Tout le monde
 * 
 * 5. Copiez l'URL du Web App et remplacez dans waitlist.vue
 */

// CONFIGUREZ VOTRE GOOGLE SHEET ID ICI
const SHEET_ID = 'VOTRE_SHEET_ID_ICI';
const SHEET_NAME = 'Waitlist'; // Nom de l'onglet

function doPost(e) {
  try {
    // Parser les données JSON
    const data = JSON.parse(e.postData.contents);
    
    // Ouvrir le Google Sheet
    const sheet = SpreadsheetApp.openById(SHEET_ID).getSheetByName(SHEET_NAME);
    
    // Si l'onglet n'existe pas, le créer
    if (!sheet) {
      const newSheet = SpreadsheetApp.openById(SHEET_ID).insertSheet(SHEET_NAME);
      // Ajouter les en-têtes
      newSheet.getRange(1, 1, 1, 4).setValues([['Email', 'Role', 'Date', 'Source']]);
      newSheet.getRange(1, 1, 1, 4).setFontWeight('bold');
    }
    
    // Préparer les données
    const rowData = [
      data.email,
      data.role,
      new Date(data.timestamp),
      data.source
    ];
    
    // Ajouter les données au sheet
    const targetSheet = sheet || SpreadsheetApp.openById(SHEET_ID).getSheetByName(SHEET_NAME);
    targetSheet.appendRow(rowData);
    
    // Optionnel : Envoi d'email de notification
    // sendNotificationEmail(data);
    
    return ContentService
      .createTextOutput(JSON.stringify({success: true, message: 'Email ajouté avec succès'}))
      .setMimeType(ContentService.MimeType.JSON);
      
  } catch (error) {
    console.error('Erreur:', error);
    return ContentService
      .createTextOutput(JSON.stringify({success: false, error: error.toString()}))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

// Fonction optionnelle pour recevoir une notification par email
function sendNotificationEmail(data) {
  const subject = `Nouvelle inscription waitlist : ${data.role}`;
  const body = `
    Nouvelle inscription à la waitlist !
    
    Email: ${data.email}
    Type: ${data.role}
    Date: ${new Date(data.timestamp).toLocaleString('fr-FR')}
    Source: ${data.source}
  `;
  
  // Remplacez par votre email
  const adminEmail = 'votre-email@gmail.com';
  
  try {
    MailApp.sendEmail(adminEmail, subject, body);
  } catch (error) {
    console.error('Erreur envoi email:', error);
  }
}

// Fonction pour gérer les requêtes GET (optionnel)
function doGet(e) {
  return ContentService
    .createTextOutput('Google Apps Script pour Waitlist opérationnel')
    .setMimeType(ContentService.MimeType.TEXT);
}

// Fonction utilitaire pour nettoyer les anciens doublons (optionnel)
function removeDuplicates() {
  const sheet = SpreadsheetApp.openById(SHEET_ID).getSheetByName(SHEET_NAME);
  const data = sheet.getDataRange().getValues();
  const emailColumn = 0; // Colonne A (0-indexée)
  
  const seen = new Set();
  const rowsToDelete = [];
  
  for (let i = 1; i < data.length; i++) { // Commencer à 1 pour ignorer l'en-tête
    const email = data[i][emailColumn];
    if (seen.has(email)) {
      rowsToDelete.push(i + 1); // +1 car les rangs sont 1-indexés
    } else {
      seen.add(email);
    }
  }
  
  // Supprimer les lignes en commençant par la fin
  for (let i = rowsToDelete.length - 1; i >= 0; i--) {
    sheet.deleteRow(rowsToDelete[i]);
  }
  
  return `${rowsToDelete.length} doublons supprimés`;
} 