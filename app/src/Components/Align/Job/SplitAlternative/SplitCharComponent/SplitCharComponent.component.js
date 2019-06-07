import React, {Component} from 'react';
import PropTypes from "prop-types";

class SplitCharComponent extends Component {
    static propTypes = {
        char: PropTypes.string,
        space: PropTypes.bool,
        signed: PropTypes.bool,
        position: PropTypes.number,
        onClick: PropTypes.func,
        onHover:  PropTypes.func,
        isIcon: PropTypes.bool,
    };

    constructor(props) {
        super(props);
        this.state = {

        };
    }

    componentDidMount() {
    }

    componentWillUnmount() {

    }

    render = () => {
        let classes = ['word'];
        if(this.props.signed){
            classes.push('signed');
        }
        return (
            <span
                className={classes.join(" ")}
                onClick={ () => { this.props.onClick(this.props.position)} }
                onMouseOver={ () => { this.props.onHover(this.props.position)} }>
                {this.props.word ? this.props.word: "&nbsp;"}
            </span>
        );
    };


}

export default SplitCharComponent;