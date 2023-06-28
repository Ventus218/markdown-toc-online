# Markdown TOC Online

Markdown TOC Online permette di generare la *table of contents* di un file scritto in Markdown.

E' possibile fruire del servizio sia attraverso una pagina web che attraverso una web API.

![Markdown TOC frontend](./doc/img/frontend.png)

## Obiettivi

Con questo progetto si vuole dispiegare due servizi all'interno di un cluster Kubernetes in cloud.

Si vuole creare una configurazione tale da permettere ai servizi di scalare in base al carico di lavoro.

Inoltre per la gestione dell'infrastruttura e delle risorse in cloud si vuole utilizzare Terraform in quanto permette di configurare le nostre risorse in maniera dichiarativa e quindi riducendone la complessità.


## I servizi in gioco

I servizi da dispiegare sono due:
- markdown-toc (l'API web)
- markdown-toc-frontend (il frontend web)

L'API web deve essere esposta su internet.

Il frontend è solo un altro modo di fruire dell'API web, in questo modo la logica applicativa non è duplicata.

<!-- TODO: immagine architettura -->
## Predisposizione alla containerizzazione

### Parametrizzazione
Siccome il codice dei servizi, una volta containerizzato, potrà essere eseguito in situazioni molto diverse è fondamentale utilizzare delle variabili d'ambiente per permetterne la parametrizzazione.

Ad esempio, il frontend ha la necessità di contattare l'API web ma non può sapere in anticipo l'indirizzo di quest'ultima.
Per questo nel [codice](./markdown-toc-frontend/serve/index.php#L14) si sono utilizzate due variabili d'ambiente per l'host e per la porta da utilizzare:

```php
$host = gethostbyname(getenv("BACKEND_HOST"));
$port = getenv("BACKEND_PORT");
$url = "http://".$host.":".$port."/markdown-toc.php";
```

Nel [Dockerfile](./markdown-toc-frontend/Dockerfile) sono state settate con dei valori di default (che sono stati scelti appositamente per quando poi andremo a dispiegare il container all'interno di Kubernetes):

<h4 id="dockerfile-env" style="visibility: hidden;"></h4>

```Dockerfile
ENV BACKEND_HOST markdown-toc
ENV BACKEND_PORT 80
```

## Creazione delle immagini

Assumendo di trovarsi nella cartella radice di questa repository, i comandi per costruire le immagini dei due servizi sono i seguenti:

```sh
docker build -t markdown-toc ./markdown-toc
docker build -t markdown-toc-frontend ./markdown-toc-frontend
```

## I file di configurazione per Kubernetes
Sia per l'API web che per il frontend vogliamo creare:
- un *Deployment*, nel quale definiremo le immagini da utilizzare per i pod e altre configurazioni
- un *Service* che fungerà da punto di accesso unico ai pod e ne bilancerà il carico
- un *Horizontal Pod Autoscaler* che farà in modo di aumentare o diminuire il numero di pod in base al carico di lavoro

Service e Deployment dell'API web: [markdown-toc.yaml](./markdown-toc.yaml)

Service e Deployment del frontend: [markdown-toc-frontend.yaml](./markdown-toc-frontend.yaml)

> **Nota**
>
> In realtà all'interno dei file di configurazione, non sono indicate le immagini docker che sono appena state create.
> Sono invece indicate le due immagini caricate da me su Docker hub.
>
> Questo è dovuto al fatto che minikube non si connette al Docker registry sull'host ma ne ha uno al suo interno e, sebbene [sarebbe possibile](https://medium.com/swlh/how-to-run-locally-built-docker-images-in-kubernetes-b28fbc32cc1d) "inviare" a minikube le immagini appena costruite, questo non sarebbe altrettanto semplice per quando poi si andrà ad utilizzare un cloud provider.

### Risoluzione dei nomi
La cosa fondamentale è fare attenzione al nome che daremo ai due Service perchè ci permetterà di sfruttare il sistema DNS interno al cluster per permettere la comunicazione tra i due diversi pod.

Infatti si è scelto *markdown-toc* come nome del Service dell'API così che coincida con il valore di default che [abbiamo settato](#dockerfile-env) nel Dockerfile del frontend.

```yaml
# markdown-toc.yaml
apiVersion: v1
kind: Service
metadata:
  name: markdown-toc # <-----
  labels:
    app: markdown-toc-app
# ...
```

### Horizontal Pod Autoscaler

I file [markdown-toc-hpa.yaml](./markdown-toc-hpa.yaml) e [markdown-toc-frontend-hpa.yaml](./markdown-toc-frontend-hpa.yaml) definiscono gli Horizontal Pod Autoscaler per entrambi i Service.

Sono quasi identici nella sostanza, per i dettagli della configurazione si leggano i commenti in [markdown-toc-hpa.yaml](./markdown-toc-hpa.yaml).

## Testing su minikube

Per prima cosa facciamo partire minikube e poi installiamo il metric server (necessario per l'Horizontal Pod Autoscaling):
```sh
minikube start
minikube addons enable metrics-server
```

<!-- Per utilizzare i service di tipo LoadBalancer è [necessario](https://minikube.sigs.k8s.io/docs/handbook/accessing/#loadbalancer-access) utilizzare minikube tunnel.
(Consiglio di aprire un termiale separato in quanto il tunnel andrà lasciato in esecuzione)
```sh
minikube tunnel
``` -->

Ora sempre dalla cartella radice della repository instanziamo i deployment e i service:
```sh
kubectl apply -f ./markdown-toc.yaml
kubectl apply -f ./markdown-toc-frontend.yaml
```

Adesso testiamo che i pod e i service funzionino.

Testiamo il backend:
```sh
curl --location "$(minikube service --url markdown-toc)/markdown-toc.php" \
--header 'Content-Type: application/json' \
--data '{
    "md-text": "# Title\r\n\r\n<!-- toc here -->\r\n\r\n## Heading2\r\nfoo\r\n\r\n### Heading3\r\nbar\r\n\r\n## foobar"
}'
```

Testiamo il frontend:
```sh
minikube service markdown-toc-frontend
```

Si noti che quando viene generata la table of contents viene anche restituito un indirizzo ip. Quello è l'indirizzo (interno al cluster) del pod che ha eseguito la richiesta.

Questo ci permette di osservare che il LoadBalancer sta distribuendo il carico tra i pod, infatti effettuando più volte la richiesta l'indirizzo in questione cambia.

Ora attiviamo gli Horizontal Pod Autoscaler:
```sh
kubectl apply -f ./markdown-toc-hpa.yaml
kubectl apply -f ./markdown-toc-frontend-hpa.yaml
```

E in un altro terminale eseguiamo:
```sh
kubectl get hpa --watch

# NAME                        REFERENCE                          TARGETS         MINPODS   MAXPODS   REPLICAS   AGE
# markdown-toc-frontend-hpa   Deployment/markdown-toc-frontend   <unknown>/50%   1         10        0          8s
# markdown-toc-hpa            Deployment/markdown-toc            <unknown>/50%   1         10        0          9s

# markdown-toc-frontend-hpa   Deployment/markdown-toc-frontend   10%/50%         1         10        2          15s
# markdown-toc-hpa            Deployment/markdown-toc            0%/50%          1         10        2          16s

# markdown-toc-frontend-hpa   Deployment/markdown-toc-frontend   10%/50%         1         10        2          45s
# markdown-toc-hpa            Deployment/markdown-toc            0%/50%          1         10        2          46s

# markdown-toc-frontend-hpa   Deployment/markdown-toc-frontend   10%/50%         1         10        1          60s
# markdown-toc-hpa            Deployment/markdown-toc            0%/50%          1         10        1          61s
```

E' normale che ci voglia più o meno tempo prima che l'hpa riesca ad ottenere le metriche dai deployment, questo è il motivo per cui si vede *\<unknown\>* nelle prime due righe.

Inizialmente le repliche sono due, come definito dal deployment nei file di configurazione yaml.

Poco dopo vediamo che le repliche calano a 1, questo succede in quanto il carico è inferiore al target e quindi l'hpa scala verso il basso il numero di pod.

Adesso testiamo anche che i pod possano scalare verso l'alto. Per farlo genereremo del carico con lo script workload.sh (carica di lavoro solo l'API web non il frontend):
```sh
# il primo parametro indica l'indirizzo al quale inviare le richieste
# il secondo invece l'intervallo di tempo in secondi tra una richiesta e l'altra

./workload.sh "$(minikube service --url markdown-toc)" 0.4
```

Continiuamo ad osservare gli hpa e noteremo che dopo poco tempo da quanto il carico è incrementato il numero di repliche torna a salire:
```sh
kubectl get hpa --watch

# NAME                        REFERENCE                          TARGETS   MINPODS   MAXPODS   REPLICAS   AGE
# markdown-toc-hpa            Deployment/markdown-toc            0%/50%    1         10        1          10m
# markdown-toc-hpa            Deployment/markdown-toc            57%/50%   1         10        1          11m
# markdown-toc-hpa            Deployment/markdown-toc            57%/50%   1         10        2          11m
# markdown-toc-hpa            Deployment/markdown-toc            36%/50%   1         10        2          12m
```

Interrompiamo il carico di workload.sh con `^C`

Puliamo il cluster minikube e stoppiamolo:
```sh
kubectl delete \
-f markdown-toc-hpa.yaml \
-f markdown-toc-frontend-hpa.yaml \
-f markdown-toc.yaml \
-f markdown-toc-frontend.yaml

minikube stop
```

Adesso che abbiamo testato il funzionamento del nostro cluster su minikube possiamo prepararci a dispiegarlo su un cloud provider.

## Distribuzione in cloud e Terraform

## I file di configurazione per Terraform

## Testing su cloud (Azure)