pipeline {  
    agent any
    environment {
		PROJECT_DIR = "/var/www/html/crm" 
        GIT_REPO_CREDS = "serhii_sivak_git_ssh"
		GIT_REPO_TO_BUILD ="git@gitlab.com:merehead/bitrihub/bitrihub_crm.git"
        GIT_BRANCH_TO_BUILD_DEV = "dev"
        SERVER_IP_DEV = "65.108.247.193"
    }
	stages {
	
	    stage('Clone repository dev') {
			when {
                branch 'dev'
            }
            steps {
                git branch: "${GIT_BRANCH_TO_BUILD_DEV}", url: "${GIT_REPO_TO_BUILD}", credentialsId: "${GIT_REPO_CREDS}"
				script { 
					env.GIT_SHA = sh(returnStdout: true, script: "git rev-parse --short HEAD").replaceAll("\\s","")
					sh 'ls -la'
					sh 'pwd .git'
				}
            }
        }
		
		stage (‘Deploy_dev’) {
			when {
                branch 'dev'
            }
		    steps {
		        sshagent(credentials : ['serhii_sivak_ssh_test_srv']){
                    sh 'rsync -avz --exclude ".git*" --exclude "install" --exclude "Jenkinsfile" --exclude ".idea" -D -e "ssh" ${WORKSPACE}/. root@${SERVER_IP_DEV}:${PROJECT_DIR} -r'
		        }
            }
        }
		
		
		stage ('Build_dev') { 
			when {
                branch 'dev'
            }
            steps {
                sshagent(credentials : ['serhii_sivak_ssh_test_srv']){
                    sh 'ssh -o StrictHostKeyChecking=no root@${SERVER_IP_DEV} "systemctl restart httpd"'
                }
		
	        }
        }

		stage ('Workspace_Clean_Up') { 
            steps {
				script {
                    cleanWs()
                }
	        }
        }

    }
}
