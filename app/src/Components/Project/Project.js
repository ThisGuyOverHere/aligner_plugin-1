import React, {Component} from 'react';

class ProjectComponent extends Component {
    constructor(props) {
        super(props);

        this.state = {
            project: {
                config: {
                    password: this.props.match.params.password,
                    id: this.props.match.params.projectID
                }
            }
        };
    }
    static getDerivedStateFromProps(props, state){
        //console.log(props,state);
        return this.state
    }
    render() {
        return (
            <div>
                <p>Project</p>
            </div>
        );
    }
}

export default ProjectComponent;