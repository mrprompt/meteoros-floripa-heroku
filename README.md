# Meteoros - Florianópolis - SC

![logo](https://bramon.s3-sa-east-1.amazonaws.com/logo-tlp-sc-transp.png)

Página para listar as capturas das estações [BRAMON](http://bramonmeteor.org/bramon/) e [GMN](https://globalmeteornetwork.org/).

## Instalação

Este projeto utiliza o [Amazon S3](https://aws.amazon.com/pt/s3/) como storage de imagens e vídeos, e para isso, 
você precisa criar o *bucket* correto e as configurar as 
[credenciais](https://docs.aws.amazon.com/pt_br/sdk-for-java/v1/developer-guide/setup-credentials.html) para utilização
do [AWS Cli](https://aws.amazon.com/pt/cli/). 

## Servidor próprio

- Clone o projeto ou baixe o zip.
- Rode o `composer install` para baixar as dependências.
- Copie o arquivo `.env.example` para `.env` e preencha os valores com as credenciais da AWS, 
  o Bucket utilizado e as estações (separadas por vírgula).

## Utilize a heroku

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy?template=https://github.com/mrprompt/meteoros-floripa/tree/master)


## Subindo as capturas

Para subir as capturas para o S3, é aconselhável a utilização do [AWS Cli](https://aws.amazon.com/pt/cli/).

### BRAMON

- Rode o *Compressor de vídeos* de sua estação e converta os vídeos para o formato *MP4*.
- Utilize o *AWS Cli* para subir o conteúdo das capturas para o bucket correto, você pode criar o _.bat_ abaixo como exemplo:
```
@echo off

cd c:\bramon\!data
aws s3 sync . s3://bramon/

echo "Pronto!"
exit
```

_Para o caso da BRAMON, não é necessário informar a estação, já que a estrutura de diretórios abaixo do !data já existe a separação por estação._

### RMS

- Rode o *CMN Bin Viewer* e converta as capturas para o formato *jpeg*.
- Utilize o *AWS Cli* para subir o conteúdo das capturas para o bucket correto, você pode criar o _.bash_ abaixo como exemplo:
```
#!/bin/bash

cd ~/RMS_Data/Archived_files/
aws s3 sync . s3://bramon/BR0004/

echo "Pronto!"
exit
```

_Substitua o BR0004 pelo identificador de sua estação, caso contrário, o script não encontrará os arquivos corretamente._
