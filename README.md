dreamland-wp-plugins
====================

Plugin a supporto di : http://ReturnToDreamland.agesci.org 


### Plugin dipendenza

* json-rest-api
* json-rest-auth
* responsive-vector-maps 

### Plugin utili 
 * [View own posts media only](https://wordpress.org/plugins/view-own-posts-media-only/)

### Installazione tramite git

Il contenuto del repo deve trovarsi nella cartella
wp-content/plugins dell'installazione di wordpress.

Utilizzando `git clone` nella cartella, il comando fallisce 
("Fatal: destination path already exists and is not an empty directory.")

Per installare correttamente usare i comandi:
```
cd mywordpress/wp-content/plugins
git init .
git remote add -t \* -f origin https://github.com/BitPrepared/dreamland-wp-plugins
git checkout master
```
Nota: la directory conterrà quindi anche altri plugin. Seleziona correttamente i file da
aggiungere al repo.

