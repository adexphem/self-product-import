# store-sync
Weebly Product Sync Service for Shapeways and MindBody Apps

## Usage  ##

This project is dockerised, below are the instructions to follow for setting up
    
    1.	Get Docker for your platform 
   - Mac : https://www.docker.com/docker-mac 	
   - Windows: https://docs.docker.com/docker-for-windows/install/#download-docker-for-windows
   - Linux: https://docs.docker.com/engine/installation/linux/docker-ce/ubuntu/

    
    2.	Of course, clone this repo [ git clone https://github.com/Weebly/store-sync.git ] 

    3.	check the project directory, if you do not have .env file then do the following

	    i.	Check if you do have .env.example file  	
	    ii.	Run `mv .env.example .env`

    4.	run `docker-compose up -d --build` to build the app image

    5.	run `docker-compose exec app php artisan key:generate`

    6.	run `docker ps`

    7.	now visit http://localhost:8889/ for http on your browser
        - http://localhost:8889/ unsecure
        - https://localhost:444/ secure

Enjoy.





