# Contenu de départ pour `storage/app/public/uploads`

Ce dossier vivait auparavant à `public/uploads`, versionné dans git au même
endroit où l'application écrit les fichiers envoyés par les vendeurs et les
clients (photos produits, preuves de paiement…). Sur Render, `public/` n'est
**pas** sur le disque persistant — seul `storage/` l'est — donc tout ce qui
était ajouté après une mise en ligne disparaissait au redéploiement suivant.

`App\Support\UploadsStorage::ensureLinked()` copie ces fichiers, une seule
fois et sans jamais écraser quoi que ce soit, vers `storage/app/public/uploads`
(qui est sur le disque persistant), puis fait de `public/uploads` un lien
(jonction sous Windows, lien symbolique ailleurs) vers cet emplacement. Le
code applicatif continue d'écrire via le disque `public_uploads`
(`config/filesystems.php`), dont la racine reste `public_path('uploads')` —
c'est le lien lui-même qui redirige silencieusement vers l'emplacement
persistant, sans qu'aucun des points d'écriture n'ait besoin de changer.

Ne rien écrire ici à la main après le premier déploiement : ce dossier n'est
lu qu'une fois, pour amorcer le disque persistant. Toute véritable image
ajoutée par la suite doit passer par le disque `public_uploads`, pas par ce
dossier.
